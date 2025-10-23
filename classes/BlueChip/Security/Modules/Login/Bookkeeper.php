<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Login;

use BlueChip\Security\Helpers\MySQLDateTime;
use BlueChip\Security\Modules\Cron\Jobs;
use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\Installable;
use wpdb;

/**
 * Storage and retrieval of lockout book-keeping data
 */
class Bookkeeper implements Initializable, Installable
{
    /**
     * @var string Name of DB table where failed logins are stored
     */
    private const FAILED_LOGINS_TABLE = 'bc_security_failed_logins';


    /**
     * @var string Name of DB table where failed logins are stored (including table prefix)
     */
    private string $failed_logins_table;


    /**
     * @param Settings $settings
     * @param wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(private Settings $settings, private wpdb $wpdb)
    {
        $this->failed_logins_table = $wpdb->prefix . self::FAILED_LOGINS_TABLE;
        $this->settings = $settings;
        $this->wpdb = $wpdb;
    }


    /**
     * @link https://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
     */
    public function install(): void
    {
        // To have dbDelta()
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();

        dbDelta(\implode(PHP_EOL, [
            "CREATE TABLE {$this->failed_logins_table} (",
            "id int unsigned NOT NULL AUTO_INCREMENT,",
            "ip_address char(128) NOT NULL,",
            "date_and_time datetime NOT NULL,",
            "username char(128) NOT NULL,",
            "user_id bigint unsigned NULL,",
            "PRIMARY KEY  (id),", // 2 spaces seems to be necessary
            "INDEX ip_address (ip_address, date_and_time)",
            ") $charset_collate;",
        ]));
    }


    public function uninstall(): void
    {
        $this->wpdb->query(\sprintf('DROP TABLE IF EXISTS %s', $this->failed_logins_table));
    }


    public function init(): void
    {
        // Hook into cron job execution.
        add_action(Jobs::FAILED_LOGINS_CLEAN_UP, $this->pruneInCron(...), 10, 0);
    }


    /**
     * Add failed login attempt from $ip_address using $username.
     *
     * @param string $ip_address
     * @param string $username
     *
     * @return int Number of non-expired failed login attempts for $ip_address.
     */
    public function recordFailedLoginAttempt(string $ip_address, string $username): int
    {
        $now = \time();
        $user = get_user_by(is_email($username) ? 'email' : 'login', $username);

        // Insert new failed login attempt for given IP address.
        $data = [
            'ip_address'    => $ip_address,
            'date_and_time' => MySQLDateTime::formatDateTime($now),
            'username'      => $username,
            'user_id'       => ($user === false) ? null : $user->ID,
        ];

        $this->wpdb->insert($this->failed_logins_table, $data, ['%s', '%s', '%s', '%d']);

        // Get count of all unexpired failed login attempts for given IP address.
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) AS retries_count FROM {$this->failed_logins_table} WHERE ip_address = %s AND date_and_time > %s",
            $ip_address,
            MySQLDateTime::formatDateTime($now - $this->settings->getResetTimeoutDuration())
        );

        return (int) $this->wpdb->get_var($query);
    }


    /**
     * Remove all expired entries from table.
     */
    public function prune(): bool
    {
        // Remove all expired entries (older than threshold).
        $threshold = \time() - $this->settings->getResetTimeoutDuration();
        // Prepare query.
        // Note: $wpdb->delete cannot be used as it does not support "<" comparison)
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "DELETE FROM {$this->failed_logins_table} WHERE date_and_time <= %s",
            MySQLDateTime::formatDateTime($threshold)
        );
        // Execute query
        $result = $this->wpdb->query($query);
        // Return result
        return $result !== false;
    }


    /**
     * @hook \BlueChip\Security\Modules\Cron\Jobs::FAILED_LOGINS_CLEAN_UP
     *
     * @internal Runs `prune` method and discards its return value.
     */
    private function pruneInCron(): void
    {
        $this->prune();
    }
}
