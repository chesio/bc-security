<?php

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Helpers\Plugin;
use BlueChip\Security\Helpers\Transients;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Log\Logger;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Login;

class Watchman implements Modules\Initializable, Modules\Activable
{
    /**
     * @var string[] List of notifications recipients
     */
    private $recipients;


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
        return \defined('BC_SECURITY_MUTE_NOTIFICATIONS') && BC_SECURITY_MUTE_NOTIFICATIONS;
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
            add_action('set_site_transient_update_core', [$this, 'watchCoreUpdateAvailable'], 10, 1);
        }
        if ($this->settings[Settings::PLUGIN_UPDATE_AVAILABLE]) {
            add_action('set_site_transient_update_plugins', [$this, 'watchPluginUpdatesAvailable'], 10, 1);
        }
        if ($this->settings[Settings::THEME_UPDATE_AVAILABLE]) {
            add_action('set_site_transient_update_themes', [$this, 'watchThemeUpdatesAvailable'], 10, 1);
        }
        if ($this->settings[Settings::ADMIN_USER_LOGIN]) {
            add_action('wp_login', [$this, 'watchWpLogin'], 10, 2);
        }
        if ($this->settings[Settings::KNOWN_IP_LOCKOUT]) {
            add_action(Login\Hooks::LOCKOUT_EVENT, [$this, 'watchLockoutEvents'], 10, 3);
        }
        if ($this->settings[Settings::CHECKLIST_ALERT]) {
            add_action(Checklist\Hooks::ADVANCED_CHECK_ALERT, [$this, 'watchChecklistSingleCheckAlert'], 10, 2);
            add_action(Checklist\Hooks::BASIC_CHECKS_ALERT, [$this, 'watchChecklistMultipleChecksAlert'], 10, 1);
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
                $message = \sprintf(
                    __('User "%s" had just deactivated BC Security plugin on your website!', 'bc-security'),
                    $user->user_login
                );
            } else {
                // No user means plugin has been probably deactivated via WP-CLI.
                // See: https://github.com/chesio/bc-security/issues/16#issuecomment-321541102
                $message = __('BC Security plugin on your website has been deactivated!', 'bc-security');
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
    public function watchCoreUpdateAvailable($update_transient): void
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
        $message = \sprintf(
            __('WordPress has an update to version %s available.', 'bc-security'),
            $latest_version
        );

        // Now it is time to make sure the method is not invoked anymore.
        remove_action('set_site_transient_update_core', [$this, 'watchCoreUpdateAvailable'], 10);

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
    public function watchPluginUpdatesAvailable($update_transient): void
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

        if (empty($plugin_updates)) {
            return;
        }

        $subject = __('Plugin updates available', 'bc-security');
        $message = [];

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

            $message[] = $plugin_message;
        }

        // Now it is time to make sure the method is not invoked anymore.
        remove_action('set_site_transient_update_plugins', [$this, 'watchPluginUpdatesAvailable'], 10);

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
    public function watchThemeUpdatesAvailable($update_transient): void
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

        if (empty($theme_updates)) {
            return;
        }

        $subject = __('Theme updates available', 'bc-security');
        $message = [];

        foreach ($theme_updates as $theme_slug => $theme_update_data) {
            $theme = wp_get_theme($theme_slug);
            $message[] = \sprintf(
                __('Theme "%1$s" has an update to version %2$s available.', 'bc-security'),
                $theme,
                $theme_update_data['new_version']
            );
        }

        // Now it is time to make sure the method is not invoked anymore.
        remove_action('set_site_transient_update_themes', [$this, 'watchThemeUpdatesAvailable'], 10);

        // Send notification.
        if ($this->notify($subject, $message) !== false) {
            foreach ($theme_updates as $theme_slug => $theme_update_data) {
                // No further notifications for this theme version.
                Transients::setForSite($theme_update_data['new_version'], 'update-notifications', 'theme', $theme_slug);
            }
        }
    }


    /**
     * Send notification if known IP has been locked out.
     *
     * @param string $remote_address
     * @param string $username
     * @param int $duration
     */
    public function watchLockoutEvents(string $remote_address, string $username, int $duration): void
    {
        if (\in_array($remote_address, $this->logger->getKnownIps(), true)) {
            $subject = __('Known IP locked out', 'bc-security');
            $message = \sprintf(
                __('A known IP address %1$s has been locked out for %2$d seconds after someone tried to log in with username "%3$s".', 'bc-security'),
                self::formatRemoteAddress($remote_address),
                $duration,
                $username
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification if user with admin privileges logged in.
     *
     * @param string $username
     * @param \WP_User $user
     */
    public function watchWpLogin(string $username, \WP_User $user): void
    {
        if (Is::admin($user)) {
            $subject = __('Admin user login', 'bc-security');
            $message = \sprintf(
                __('User "%1$s" with administrator privileges just logged in to your WordPress site from IP address %2$s.', 'bc-security'),
                $username,
                self::formatRemoteAddress($this->remote_address)
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification about single check that failed during checklist monitoring.
     *
     * @param \BlueChip\Security\Modules\Checklist\Check $check
     * @param \BlueChip\Security\Modules\Checklist\CheckResult $result
     */
    public function watchChecklistSingleCheckAlert(Checklist\Check $check, Checklist\CheckResult $result): void
    {
        $subject = __('Checklist monitoring alert', 'bc-security');
        $preamble = [
            \sprintf(__('An issue has been found during checklist monitoring of "%s" check:', 'bc-security'), $check->getName()),
            '',
        ];

        $this->notify($subject, \array_merge($preamble, $result->getMessage()));
    }


    /**
     * Send notification about multiple checks that failed during checklist monitoring.
     *
     * @param array{check:Checklist\Check,result:Checklist\CheckResult} $issues Issues which triggered the alert.
     */
    public function watchChecklistMultipleChecksAlert(array $issues): void
    {
        $subject = __('Checklist monitoring alert', 'bc-security');
        $message = [
            __('Following checks had failed during checklist monitoring:', 'bc-security'),
        ];

        foreach ($issues as $issue) {
            $message[] = '';
            $message[] = \sprintf("%s: %s", $issue['check']->getName(), $issue['result']->getMessageAsPlainText());
        }

        $this->notify($subject, $message);
    }


    /**
     * Send email notification with given $subject and $message to recipients configured in plugin settings.
     *
     * @param string $subject
     * @param string|string[] $message
     *
     * @return bool|null Null if there are no recipients configured. True if email has been sent, false otherwise.
     */
    private function notify(string $subject, $message): ?bool
    {
        return empty($this->recipients) ? null : Mailman::send($this->recipients, $subject, $message);
    }
}
