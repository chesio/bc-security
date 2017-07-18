<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Modules\Log\Logger;
use BlueChip\Security\Modules\Login\Hooks;

class Watchman implements \BlueChip\Security\Modules\Initializable, \BlueChip\Security\Modules\Deactivateable
{
    /** @var string */
    private $remote_address;

    /** @var \BlueChip\Security\Modules\Notifications\Settings */
    private $settings;

    /** @var \BlueChip\Security\Modules\Log\Logger */
    private $logger;


    /**
     * @param \BlueChip\Security\Modules\Notifications\Settings $settings
     * @param string $remote_address Remote IP address.
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
    public function __construct(Settings $settings, $remote_address, Logger $logger)
    {
        $this->remote_address = $remote_address;
        $this->settings = $settings;
        $this->logger = $logger;
    }


    /**
     * Initialize notification according to settings.
     */
    public function init()
    {
        if ($this->settings[Settings::ADMIN_USER_LOGIN]) {
            add_action('wp_login', [$this, 'watchWpLogin'], 10, 2);
        }
        if ($this->settings[Settings::KNOWN_IP_LOCKOUT]) {
            add_action(Hooks::LOCKOUT_EVENT, [$this, 'watchLockoutEvents'], 10, 3);
        }
    }


    /**
     * Run on plugin deactivation.
     */
    public function deactivate()
    {
        if ($this->settings[Settings::PLUGIN_DEACTIVATED]) {
            // Get the bastard that turned us off!
            $user = wp_get_current_user();

            $subject = __('BC Security deactivated', 'bc-security');
            $message = sprintf(
                __('User "%s" just deactivated BC Security plugin on your website!', 'bc-security'),
                $user->user_login
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * @param string $remote_address
     * @param string $username
     * @param int $duration
     */
    public function watchLockoutEvents($remote_address, $username, $duration)
    {
        if (in_array($remote_address, $this->logger->getKnownIps(), true)) {
            $subject = __('Known IP locked out', 'bc-security');
            $message = sprintf(
                __('A known IP address %1$s has been locked out for %2$d seconds after someone tried to log in with username "%3$s".', 'bc-security'),
                $remote_address,
                $duration,
                $username
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * @param string $username
     * @param \WP_User $user
     */
    public function watchWpLogin($username, $user)
    {
        if (Is::admin($user)) {
            $subject = __('Admin user login', 'bc-security');
            $message = sprintf(
                __('User "%1$s" with administrator privileges just logged in to your WordPress site from IP address %2$s.', 'bc-security'),
                $username,
                $this->remote_address
            );

            $this->notify($subject, $message);
        }
    }


    /**
     * Send the notification with given $subject and $message to recipients
     * configured in plugin settings.
     *
     * @param string $subject
     * @param array|string $message
     */
    private function notify($subject, $message)
    {
        // Get recipients.
        $to = $this->settings[Settings::NOTIFICATION_RECIPIENTS];

        // If site admin should be notified to, include him as well.
        if ($this->settings[Settings::NOTIFY_SITE_ADMIN]) {
            array_unshift($to, get_option('admin_email'));
        }

        if (!empty($to)) {
            Mailman::send($to, $subject, $message);
        }
    }
}
