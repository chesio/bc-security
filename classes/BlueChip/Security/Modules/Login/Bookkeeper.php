<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Login;

/**
 * Storage and retrieval of lockout book-keeping data
 */
class Bookkeeper implements \BlueChip\Security\Modules\Installable
{
    /**
     * @var string Name of DB table where failed logins are stored
     */
    const FAILED_LOGINS_TABLE = 'bc_security_failed_logins';

    /**
     * @var string Date format accepted by MySQL
     */
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var string Name of DB table where failed logins are stored (including table prefix)
     */
    private $failed_logins_table;

    /**
     * @var \BlueChip\Security\Modules\Login\Settings
     */
    private $settings;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * @param \BlueChip\Security\Modules\Login\Settings $settings
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(Settings $settings, \wpdb $wpdb)
    {
        $this->failed_logins_table = $wpdb->prefix . self::FAILED_LOGINS_TABLE;
        $this->settings = $settings;
        $this->wpdb = $wpdb;
    }


    /**
     * @link https://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
     */
    public function install()
    {
        // To have dbDelta()
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();

        dbDelta(implode(PHP_EOL, [
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


    public function uninstall()
    {
        $this->wpdb->query(sprintf('DROP TABLE IF EXISTS %s', $this->failed_logins_table));
    }


    /**
     * Add failed login attempt from $ip_address using $username.
     *
     * @param string $ip_address
     * @param string $username
     * @return int Number of non-expired failed login attempts for $ip_address.
     */
    public function recordFailedLoginAttempt($ip_address, $username)
    {
        $now = current_time('timestamp');
        $user = get_user_by(is_email($username) ? 'email' : 'login', $username);

        // Insert new failed login attempt for given IP address.
        $data = [
            'ip_address'    => $ip_address,
            'date_and_time' => date(self::MYSQL_DATETIME_FORMAT, $now),
            'username'      => $username,
            'user_id'       => ($user === false) ? null : $user->ID,
        ];

        $this->wpdb->insert($this->failed_logins_table, $data, ['%s', '%s', '%s', '%d']);

        // Get count of all unexpired failed login attempts for given IP address.
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) AS retries_count FROM {$this->failed_logins_table} WHERE ip_address = %s AND date_and_time > %s",
            $ip_address,
            date(self::MYSQL_DATETIME_FORMAT, $now - $this->settings->getResetTimeoutDuration())
        );

        return $this->wpdb->get_var($query);
    }


    /**
     * Remove all expired entries from table.
     *
     * @return mixed
     */
    public function prune()
    {
        // Remove all expired entries (older than threshold).
        $threshold = current_time('timestamp') - $this->settings->getResetTimeoutDuration();
        // Prepare query.
        // Note: $wpdb->delete cannot be used as it does not support "<" comparison)
        $query = $this->wpdb->prepare(
            "DELETE FROM {$this->failed_logins_table} WHERE date_and_time <= %s",
            date(self::MYSQL_DATETIME_FORMAT, $threshold)
        );
        // Execute query.
        return $this->wpdb->query($query);
    }
}
