<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Helpers\Plugin;
use BlueChip\Security\Helpers\Transients;
use BlueChip\Security\Modules\Activable;
use BlueChip\Security\Modules\BadRequestsBanner\BanRule;
use BlueChip\Security\Modules\BadRequestsBanner\Hooks as BadRequestBannerHooks;
use BlueChip\Security\Modules\Checklist\Check;
use BlueChip\Security\Modules\Checklist\CheckResult;
use BlueChip\Security\Modules\Checklist\Hooks as ChecklistHooks;
use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\Log\Logger;
use BlueChip\Security\Modules\Login\Hooks as LoginHooks;
use WP_User;

class Watchman implements Activable, Initializable
{
    /**
     * @var string[] List of notifications recipients
     */
    private array $recipients;


    /**
     * @var string[] List of sent notifications.
     */
    private array $sent_notifications = [];


    /**
     * @param Settings $settings
     * @param string $remote_address Remote IP address.
     * @param Logger $logger
     */
    public function __construct(private Settings $settings, private string $remote_address, private Logger $logger)
    {
        // Get recipients.
        $this->recipients = $settings[Settings::NOTIFICATION_RECIPIENTS];
        // If site admin should be notified to, include him as well.
        if ($settings[Settings::NOTIFY_SITE_ADMIN]) {
            \array_unshift($this->recipients, get_option('admin_email'));
        }
    }


    /**
     * @return bool True if notifications are muted via `BC_SECURITY_MUTE_NOTIFICATIONS` constant, false otherwise.
     */
    public static function isMuted(): bool
    {
        return \defined('BC_SECURITY_MUTE_NOTIFICATIONS') && \constant('BC_SECURITY_MUTE_NOTIFICATIONS');
    }


    /**
     * Format remote IP address - append result of reverse DNS lookup if successful.
     *
     * @param string $remote_address
     *
     * @return string
     */
    private static function formatRemoteAddress(string $remote_address): string
    {
        $remote_hostname = \gethostbyaddr($remote_address);
        if (empty($remote_hostname) || ($remote_hostname === $remote_address)) {
            return $remote_address;
        } else {
            return "{$remote_address} ({$remote_hostname})";
        }
    }


    /**
     * Initialize notification according to settings.
     */
    public function init(): void
    {
        // Bail early if no recipients are set or we are explicitly ordered to not disturb.
        if (empty($this->recipients) || self::isMuted()) {
            return;
        }

        if ($this->settings[Settings::CORE_UPDATE_AVAILABLE]) {
            add_action('set_site_transient_update_core', $this->watchCoreUpdateAvailable(...), 10, 1);
        }
        if ($this->settings[Settings::PLUGIN_UPDATE_AVAILABLE]) {
            add_action('set_site_transient_update_plugins', $this->watchPluginUpdatesAvailable(...), 10, 1);
        }
        if ($this->settings[Settings::THEME_UPDATE_AVAILABLE]) {
            add_action('set_site_transient_update_themes', $this->watchThemeUpdatesAvailable(...), 10, 1);
        }
        if ($this->settings[Settings::ADMIN_USER_LOGIN]) {
            add_action('wp_login', $this->watchWpLogin(...), 10, 2);
        }
        if ($this->settings[Settings::KNOWN_IP_LOCKOUT]) {
            add_action(BadRequestBannerHooks::BAD_REQUEST_EVENT, $this->watchBadRequestBanEvents(...), 10, 3);
            add_action(LoginHooks::LOCKOUT_EVENT, $this->watchLockoutEvents(...), 10, 3);
        }
        if ($this->settings[Settings::CHECKLIST_ALERT]) {
            add_action(ChecklistHooks::ADVANCED_CHECK_ALERT, $this->watchChecklistSingleCheckAlert(...), 10, 2);
            add_action(ChecklistHooks::BASIC_CHECKS_ALERT, $this->watchChecklistMultipleChecksAlert(...), 10, 1);
        }
    }


    public function activate(): void
    {
        // Do nothing.
    }


    /**
     * Send notification that plugin has been deactivated.
     */
    public function deactivate(): void
    {
        // Bail early if no recipients are set.
        if (empty($this->recipients)) {
            return;
        }

        if ($this->settings[Settings::PLUGIN_DEACTIVATED]) {
            $subject = __('BC Security deactivated', 'bc-security');

            $user = wp_get_current_user();
            if ($user->ID) {
                // Name the bastard that turned us off!
                $message = new Message(
                    \sprintf(
                        __('User "%s" had just deactivated BC Security plugin on your website!', 'bc-security'),
                        $user->user_login
                    )
                );
            } else {
                // No user means plugin has been probably deactivated via WP-CLI.
                // See: https://github.com/chesio/bc-security/issues/16#issuecomment-321541102
                $message = new Message(__('BC Security plugin on your website has been deactivated!', 'bc-security'));
            }

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification when WordPress update is available.
     *
     * @see get_preferred_from_update_core()
     * @see get_core_updates()
     *
     * @param object $update_transient
     */
    private function watchCoreUpdateAvailable($update_transient): void
    {
        // Check if update transient has the data we are interested in.
        if (!isset($update_transient->updates) || !\is_array($update_transient->updates) || empty($update_transient->updates)) {
            return;
        }

        // Get first update item (should be "upgrade" response).
        $update = $update_transient->updates[0];
        if (!isset($update->response) || ($update->response !== 'upgrade') || !isset($update->current)) {
            // Not the expected response.
            return;
        }

        // Get latest WP version available.
        $latest_version = $update->current;

        // Already notified about this update?
        if ($latest_version === Transients::getForSite('update-notifications', 'core')) {
            return;
        }

        $subject = __('WordPress update available', 'bc-security');
        $message = new Message(
            \sprintf(
                __('WordPress has an update to version %s available.', 'bc-security'),
                $latest_version,
            )
        );

        // Send notification.
        if ($this->notify($subject, $message) !== false) {
            // No further notifications for this update.
            Transients::setForSite($latest_version, 'update-notifications', 'core');
        }
    }


    /**
     * Send notification if there are plugin updates available.
     *
     * @param object $update_transient
     */
    private function watchPluginUpdatesAvailable($update_transient): void
    {
        // Check if update transient has the data we are interested in.
        if (!isset($update_transient->response) || !\is_array($update_transient->response)) {
            return;
        }

        // Filter out any updates for which notification has been sent already.
        $plugin_updates = \array_filter($update_transient->response, function ($plugin_update_data, $plugin_file) {
            $notified_version = Transients::getForSite('update-notifications', 'plugin', $plugin_file);
            return empty($notified_version) || \version_compare($notified_version, $plugin_update_data->new_version, '<');
        }, ARRAY_FILTER_USE_BOTH);

        if ($plugin_updates !== []) {
            if (apply_filters(Hooks::ALL_PLUGIN_UPDATES_IN_ONE_NOTIFICATION, false)) {
                $this->notifyAboutPluginUpdatesAvailable($plugin_updates);
            } else {
                foreach ($plugin_updates as $plugin_file => $plugin_update_data) {
                    $this->notifyAboutPluginUpdatesAvailable([$plugin_file => $plugin_update_data]);
                }
            }
        }
    }


    /**
     * @param array<string,object> $plugin_updates Plugin file and related update object.
     */
    private function notifyAboutPluginUpdatesAvailable(array $plugin_updates): void
    {
        $subject = __('Plugin updates available', 'bc-security');
        $message = new Message();

        foreach ($plugin_updates as $plugin_file => $plugin_update_data) {
            $plugin_data = Plugin::getPluginData($plugin_file);
            $plugin_message = \sprintf(
                __('Plugin "%1$s" has an update to version %2$s available.', 'bc-security'),
                $plugin_data['Name'],
                $plugin_update_data->new_version
            );

            if (!empty($plugin_changelog_url = Plugin::getChangelogUrl($plugin_file, $plugin_data))) {
                // Append link to changelog, if available.
                $plugin_message .= ' ' . \sprintf(
                    __('Changelog: %1$s', 'bc-security'),
                    $plugin_changelog_url
                );
            }

            $message->addLine($plugin_message);
        }

        // Send notification.
        if ($this->notify($subject, $message) !== false) {
            foreach ($plugin_updates as $plugin_file => $plugin_update_data) {
                // No further notifications for this plugin version.
                Transients::setForSite($plugin_update_data->new_version, 'update-notifications', 'plugin', $plugin_file);
            }
        }
    }


    /**
     * Send notification if there are theme updates available.
     *
     * @param object $update_transient
     */
    private function watchThemeUpdatesAvailable($update_transient): void
    {
        // Check if update transient has the data we are interested in.
        if (!isset($update_transient->response) || !\is_array($update_transient->response)) {
            return;
        }

        // Filter out any updates for which notification has been sent already.
        $theme_updates = \array_filter($update_transient->response, function ($theme_update_data, $theme_slug) {
            $last_version = Transients::getForSite('update-notifications', 'theme', $theme_slug);
            return empty($last_version) || \version_compare($last_version, $theme_update_data['new_version'], '<');
        }, ARRAY_FILTER_USE_BOTH);

        if ($theme_updates !== []) {
            if (apply_filters(Hooks::ALL_THEME_UPDATES_IN_ONE_NOTIFICATION, false)) {
                $this->notifyAboutThemeUpdatesAvailable($theme_updates);
            } else {
                foreach ($theme_updates as $theme_slug => $theme_update_data) {
                    $this->notifyAboutThemeUpdatesAvailable([$theme_slug => $theme_update_data]);
                }
            }
        }
    }


    /**
     * @param array<string,object> $theme_updates Theme slug and related update object.
     */
    private function notifyAboutThemeUpdatesAvailable(array $theme_updates): void
    {
        $subject = __('Theme updates available', 'bc-security');
        $message = new Message();

        foreach ($theme_updates as $theme_slug => $theme_update_data) {
            $theme = wp_get_theme($theme_slug);
            $message->addLine(
                \sprintf(
                    __('Theme "%1$s" has an update to version %2$s available.', 'bc-security'),
                    $theme,
                    $theme_update_data['new_version'],
                )
            );
        }

        // Send notification.
        if ($this->notify($subject, $message) !== false) {
            foreach ($theme_updates as $theme_slug => $theme_update_data) {
                // No further notifications for this theme version.
                Transients::setForSite($theme_update_data['new_version'], 'update-notifications', 'theme', $theme_slug);
            }
        }
    }


    /**
     * Send notification if known IP has been locked out as result of bad request.
     */
    private function watchBadRequestBanEvents(string $remote_address, string $uri, BanRule $ban_rule): void
    {
        if (\in_array($remote_address, $this->logger->getKnownIps(), true)) {
            $subject = __('Known IP locked out', 'bc-security');
            $message = new Message(
                \sprintf(
                    __('A known IP address %1$s has been locked out due to bad request rule "%2$s" after someone tried to access following URL: %3$s', 'bc-security'),
                    self::formatRemoteAddress($remote_address),
                    $ban_rule->getName(),
                    $uri,
                )
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification if known IP has been locked out as result of failed login.
     */
    private function watchLockoutEvents(string $remote_address, string $username, int $duration): void
    {
        if (\in_array($remote_address, $this->logger->getKnownIps(), true)) {
            $subject = __('Known IP locked out', 'bc-security');
            $message = new Message(
                \sprintf(
                    __('A known IP address %1$s has been locked out for %2$d seconds after someone tried to log in with username "%3$s".', 'bc-security'),
                    self::formatRemoteAddress($remote_address),
                    $duration,
                    $username,
                )
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification if user with admin privileges logged in.
     */
    private function watchWpLogin(string $username, WP_User $user): void
    {
        if (Is::admin($user)) {
            $subject = __('Admin user login', 'bc-security');
            $message = new Message(
                \sprintf(
                    __('User "%1$s" with administrator privileges just logged in to your WordPress site from IP address %2$s.', 'bc-security'),
                    $username,
                    self::formatRemoteAddress($this->remote_address),
                )
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification about single check that failed during checklist monitoring.
     */
    private function watchChecklistSingleCheckAlert(Check $check, CheckResult $result): void
    {
        $subject = __('Checklist monitoring alert', 'bc-security');
        $message = new Message(
            \sprintf(
                __('An issue has been found during checklist monitoring of "%s" check:', 'bc-security'),
                $check->getName(),
            )
        );

        $message->addEmptyLine();
        $message->addLines($result->getMessage());

        $this->notify($subject, $message);
    }


    /**
     * Send notification about multiple checks that failed during checklist monitoring.
     *
     * @param array<int,array{check:Check,result:CheckResult}> $issues Issues which triggered the alert.
     */
    private function watchChecklistMultipleChecksAlert(array $issues): void
    {
        $subject = __('Checklist monitoring alert', 'bc-security');
        $message = new Message(
            __('Following checks had failed during checklist monitoring:', 'bc-security'),
        );

        foreach ($issues as $issue) {
            $message
                ->addEmptyLine()
                ->addLine(\sprintf("%s: %s", $issue['check']->getName(), $issue['result']->getMessageAsPlainText()))
            ;
        }

        $this->notify($subject, $message);
    }


    /**
     * Send email notification with given $subject and $message to recipients configured in plugin settings.
     *
     * @param string $subject
     * @param Message $message
     *
     * @return bool|null Null if there are no recipients configured. True if email has been sent, false otherwise.
     */
    private function notify(string $subject, Message $message): ?bool
    {
        if ($this->hasMessageBeenSent($message)) {
            // Given message has been sent already.
            return true;
        }

        $status = empty($this->recipients) ? null : Mailman::send($this->recipients, $subject, $message);

        if ($status) {
            $this->markMessageAsSent($message);
        }

        return $status;
    }


    private function hasMessageBeenSent(Message $message): bool
    {
        return \in_array($message->getFingerprint(), $this->sent_notifications, true);
    }


    private function markMessageAsSent(Message $message): void
    {
        $this->sent_notifications[] = $message->getFingerprint();
    }
}
