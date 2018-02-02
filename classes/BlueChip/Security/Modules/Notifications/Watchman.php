<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Helpers\Transients;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Log\Logger;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Checksums;
use BlueChip\Security\Modules\Login;

class Watchman implements Modules\Loadable, Modules\Initializable, Modules\Activable
{
    /**
     * @var string Remote IP address
     */
    private $remote_address;

    /**
     * @var \BlueChip\Security\Modules\Notifications\Settings
     */
    private $settings;

    /**
     * @var \BlueChip\Security\Modules\Log\Logger
     */
    private $logger;

    /**
     * @var array List of notifications recipients
     */
    private $recipients;


    /**
     * @param \BlueChip\Security\Modules\Notifications\Settings $settings
     * @param string $remote_address Remote IP address.
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
    public function __construct(Settings $settings, string $remote_address, Logger $logger)
    {
        $this->remote_address = $remote_address;
        $this->settings = $settings;
        $this->logger = $logger;

        // Get recipients.
        $this->recipients = $settings[Settings::NOTIFICATION_RECIPIENTS];
        // If site admin should be notified to, include him as well.
        if ($settings[Settings::NOTIFY_SITE_ADMIN]) {
            array_unshift($this->recipients, get_option('admin_email'));
        }
    }


    /**
     * Format remote IP address - append result of reverse DNS lookup, if successful.
     *
     * @param string $remote_address
     * @return string
     */
    private static function formatRemoteAddress(string $remote_address): string
    {
        $remote_hostname = gethostbyaddr($remote_address);
        if (empty($remote_hostname) || ($remote_hostname === $remote_address)) {
            return $remote_address;
        } else {
            return "{$remote_address} ({$remote_hostname})";
        }
    }


    public function load()
    {
        // Bail early, if no recipients are set.
        if (empty($this->recipients)) {
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
    }


    /**
     * Initialize notification according to settings.
     */
    public function init()
    {
        // Bail early, if no recipients are set.
        if (empty($this->recipients)) {
            return;
        }

        if ($this->settings[Settings::ADMIN_USER_LOGIN]) {
            add_action('wp_login', [$this, 'watchWpLogin'], 10, 2);
        }
        if ($this->settings[Settings::KNOWN_IP_LOCKOUT]) {
            add_action(Login\Hooks::LOCKOUT_EVENT, [$this, 'watchLockoutEvents'], 10, 3);
        }
        if ($this->settings[Settings::CORE_CHECKSUMS_VERIFICATION_ERROR]) {
            add_action(Checksums\Hooks::CORE_CHECKSUMS_RETRIEVAL_FAILED, [$this, 'watchCoreChecksumsRetrievalFailed'], 10, 1);
            add_action(Checksums\Hooks::CORE_CHECKSUMS_VERIFICATION_ALERT, [$this, 'watchCoreChecksumsVerificationAlert'], 10, 2);
        }
        if ($this->settings[Settings::PLUGIN_CHECKSUMS_VERIFICATION_ERROR]) {
            add_action(Checksums\Hooks::PLUGIN_CHECKSUMS_RETRIEVAL_FAILED, [$this, 'watchPluginChecksumsRetrievalFailed'], 10, 1);
            add_action(Checksums\Hooks::PLUGIN_CHECKSUMS_VERIFICATION_ALERT, [$this, 'watchPluginChecksumsVerificationAlert'], 10, 1);
        }
        if ($this->settings[Settings::CHECKLIST_ALERT]) {
            add_action(Checklist\Hooks::CHECK_ALERT, [$this, 'watchChecklistAlert'], 10, 1);
        }
    }


    public function activate()
    {
        // Do nothing.
    }


    /**
     * Send notification that plugin has been deactivated.
     */
    public function deactivate()
    {
        // Bail early, if no recipients are set.
        if (empty($this->recipients)) {
            return;
        }

        if ($this->settings[Settings::PLUGIN_DEACTIVATED]) {
            $subject = __('BC Security deactivated', 'bc-security');

            $user = wp_get_current_user();
            if ($user->ID) {
                // Name the bastard that turned us off!
                $message = sprintf(
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
    public function watchCoreUpdateAvailable($update_transient)
    {
        // Check, if update transient has the data we are interested in.
        if (!isset($update_transient->updates) || !is_array($update_transient->updates) || empty($update_transient->updates)) {
            return;
        }

        // Get first update item (should be "upgrade" response).
        $update = $update_transient->updates[0];
        if (!isset($update->response) || ($update->response !== 'upgrade')) {
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
        $message = sprintf(
            __('WordPress has an update to version %s available.', 'bc-security'),
            $latest_version
        );

        // Now it is time to make sure the method is not invoked anymore.
        remove_action('set_site_transient_update_core', [$this, 'watchCoreUpdateAvailable'], 10, 1);

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
    public function watchPluginUpdatesAvailable($update_transient)
    {
        // Check, if update transient has the data we are interested in.
        if (!isset($update_transient->response) || !is_array($update_transient->response)) {
            return;
        }

        // Filter out any updates for which notification has been sent already.
        $plugin_updates = array_filter($update_transient->response, function ($plugin_update_data, $plugin_file) {
            $notified_version = Transients::getForSite('update-notifications', 'plugin', $plugin_file);
            return empty($notified_version) || version_compare($notified_version, $plugin_update_data->new_version, '<');
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($plugin_updates)) {
            return;
        }

        $subject = __('Plugin updates available', 'bc-security');
        $message = [];

        foreach ($plugin_updates as $plugin_file => $plugin_update_data) {
            // Note: get_plugin_data() function is only defined in admin,
            // but it seems that it is always available in this context...
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            $message[] = sprintf(
                __('Plugin "%1$s" has an update to version %2$s available.', 'bc-security'),
                $plugin_data['Name'],
                $plugin_update_data->new_version
            );
        }

        // Now it is time to make sure the method is not invoked anymore.
        remove_action('set_site_transient_update_plugins', [$this, 'watchPluginUpdatesAvailable'], 10, 1);

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
    public function watchThemeUpdatesAvailable($update_transient)
    {
        // Check, if update transient has the data we are interested in.
        if (!isset($update_transient->response) || !is_array($update_transient->response)) {
            return;
        }

        // Filter out any updates for which notification has been sent already.
        $theme_updates = array_filter($update_transient->response, function ($theme_update_data, $theme_slug) {
            $last_version = Transients::getForSite('update-notifications', 'theme', $theme_slug);
            return empty($last_version) || version_compare($last_version, $theme_update_data['new_version'], '<');
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($theme_updates)) {
            return;
        }

        $subject = __('Theme updates available', 'bc-security');
        $message = [];

        foreach ($theme_updates as $theme_slug => $theme_update_data) {
            $theme = wp_get_theme($theme_slug);
            $message[] = sprintf(
                __('Theme "%1$s" has an update to version %2$s available.', 'bc-security'),
                $theme,
                $theme_update_data['new_version']
            );
        }

        // Now it is time to make sure the method is not invoked anymore.
        remove_action('set_site_transient_update_themes', [$this, 'watchThemeUpdatesAvailable'], 10, 1);

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
    public function watchLockoutEvents(string $remote_address, string $username, int $duration)
    {
        if (in_array($remote_address, $this->logger->getKnownIps(), true)) {
            $subject = __('Known IP locked out', 'bc-security');
            $message = sprintf(
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
    public function watchWpLogin(string $username, \WP_User $user)
    {
        if (Is::admin($user)) {
            $subject = __('Admin user login', 'bc-security');
            $message = sprintf(
                __('User "%1$s" with administrator privileges just logged in to your WordPress site from IP address %2$s.', 'bc-security'),
                $username,
                self::formatRemoteAddress($this->remote_address)
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send notification if checksums verification found modified or unknown files in WordPress directories.
     *
     * @param array $modified_files Files for which official checksums do not match.
     * @param array $unknown_files Files that are present on file system but not in official checksums.
     */
    public function watchCoreChecksumsVerificationAlert(array $modified_files, array $unknown_files)
    {
        $subject = __('Checksums verification alert', 'bc-security');
        $message = [
            __('There have been modified or unknown files found in directories where WordPress is installed.'),
        ];

        if (!empty($modified_files)) {
            $message[] = __('Official checksums do not match for the following files:', 'bc-security');
            $message = array_merge($message, $modified_files);
        }

        if (!empty($unknown_files)) {
            if (!empty($message)) {
                $message[] = '';
            }
            $message[] = __('Following files are present on the file system, but not in official checksums:', 'bc-security');
            $message = array_merge($message, $unknown_files);
        }

        // Append list of matched files to the message and send an email.
        $this->notify($subject, $message);
    }


    /**
     * Send notification if checksums verification found modified or unknown files in plugin directories.
     *
     * @param array $plugins Plugins for which checksums verification triggered an alert.
     */
    public function watchPluginChecksumsVerificationAlert(array $plugins)
    {
        $subject = __('Plugin checksums verification alert', 'bc-security');
        $message = [
            __('Checksums verification for the following plugins triggered an alert:', 'bc-security'),
        ];

        foreach ($plugins as $plugin_basename => $plugin_data) {
            $message[] = '';
            $message[] = sprintf("%s (%s)", $plugin_data['Name'], $plugin_basename);

            if (!empty($plugin_data['ModifiedFiles'])) {
                $message[] = __('Checksums do not match for the following files:', 'bc-security');
                $message = array_merge($message, $plugin_data['ModifiedFiles']);
            }

            if (!empty($plugin_data['UnknownFiles'])) {
                $message[] = __('Following files are present on the file system, but not in checksums:', 'bc-security');
                $message = array_merge($message, $plugin_data['UnknownFiles']);
            }
        }

        // Append list of matched files to the message and send an email.
        $this->notify($subject, $message);
    }


    /**
     * Send notification if checksums retrieval via WordPress.org API failed.
     *
     * @param string $url
     */
    public function watchCoreChecksumsRetrievalFailed(string $url)
    {
        $subject = __('Checksums verification failed', 'bc-security');
        $message = sprintf(
            __('Checksums verification for WordPress core has been aborted, because checksums could not be fetched from %s.', 'bc-security'),
            $url
        );

        $this->notify($subject, $message);
    }


    /**
     * Send notifications if checksums retrieval for plugins via WordPress.org failed.
     *
     * @param array $plugins Plugins for which checksums could not be retrieved.
     */
    public function watchPluginChecksumsRetrievalFailed(array $plugins)
    {
        $subject = __('Plugin checksums verification failed', 'bc-security');
        $message = [
            __('Checksums verification for the following plugins has been aborted, because checksums could not be fetched from remote server:', 'bc-security'),
            '',
        ];

        foreach ($plugins as $plugin_basename => $plugin_data) {
            $message[] = sprintf(
                __("%s (%s, version %s): failed to fetch checksums from %s", 'bc-security'),
                $plugin_basename,
                $plugin_data['Name'],
                $plugin_data['Version'],
                $plugin_data['Checksums URL']
            );
        }

        $this->notify($subject, $message);
    }


    /**
     * Send notification if there has been checklist alert triggered.
     *
     * @param array $issues Issues which triggered the alert (issue is an array with 'check' and 'result' keys).
     */
    public function watchChecklistAlert(array $issues)
    {
        $subject = __('Checklist alert', 'bc-security');
        $message = [
            __('Following issues have been found by automatic checklist check:'),
        ];

        foreach ($issues as $issue) {
            $message[] = '';
            $message[] = sprintf("%s: %s", $issue['check']->getName(), strip_tags($issue['result']->getMessage()));
        }

        $this->notify($subject, $message);
    }


    /**
     * Send email notification with given $subject and $message to recipients configured in plugin settings.
     *
     * @param string $subject
     * @param array|string $message
     * @return null|false|true Null, if there are no recipients configured. True, if email has been sent, false otherwise.
     */
    private function notify(string $subject, $message)
    {
        return empty($this->recipients) ? null : Mailman::send($this->recipients, $subject, $message);
    }
}
