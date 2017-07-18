<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\IpBlacklist;

/**
 * Who's on the blacklist, baby?
 *
 * Note on blacklist release time with respect to <, =, > comparisons against
 * current time: item is locked (lock is active), if release time is in the
 * future, in other words: release_time > current_time. Otherwise, the item is
 * not locked (lock is expired).
 */
class Manager implements \BlueChip\Security\Modules\Installable
{
    /** @var string Name of DB table where IP blacklist is stored */
    const BLACKLIST_TABLE = 'bc_security_ip_blacklist';

    /** @var string Date format accepted by MySQL */
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var string Name of DB table where blacklist is stored (including table prefix) */
    private $blacklist_table;

    /** @var array */
    private $columns;

    /** @var \wpdb */
    private $wpdb;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct($wpdb)
    {
        $this->blacklist_table = $wpdb->prefix . self::BLACKLIST_TABLE;
        $this->columns = [
            'id', 'scope', 'ip_address', 'ban_time', 'release_time', 'reason',
        ];
        $this->wpdb = $wpdb;
    }


    public function install()
    {
        // To have dbDelta()
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(implode(' ', [
            "CREATE TABLE {$this->blacklist_table} (",
            "id int unsigned NOT NULL AUTO_INCREMENT,",
            "scope tinyint unsigned NOT NULL,",
            "ip_address char(128) NOT NULL,",
            "ban_time datetime NOT NULL,",
            "release_time datetime NOT NULL,",
            "reason tinyint unsigned NOT NULL,",
            "PRIMARY KEY  (id),", // 2 spaces seems to be necessary
            "UNIQUE KEY ip_in_scope (scope, ip_address)",
            ");",
        ]));
    }


    public function uninstall()
    {
        $this->wpdb->query(sprintf('DROP TABLE IF EXISTS %s', $this->blacklist_table));
    }


    /**
     * Return number of all records on blacklist (active and expired).
     *
     * @param int $scope
     * @return int
     */
    public function countAll($scope = LockScope::ANY)
    {
        $query = "SELECT COUNT(id) AS total FROM {$this->blacklist_table}";

        if ($scope !== LockScope::ANY) {
            $query .= $this->wpdb->prepare(" WHERE scope = %d", $scope);
        }

        return intval($this->wpdb->get_var($query));
    }


    /**
     * Fetch all items on blacklist that match provided arguments.
     *
     * @param int $scope
     * @param int $from
     * @param int $limit
     * @param string $order_by
     * @param string $order
     * @return array
     */
    public function fetch($scope = LockScope::ANY, $from = 0, $limit = 20, $order_by = null, $order = null)
    {
        // Prepare query
        $query = "SELECT * FROM {$this->blacklist_table}";

        // Apply scope if given
        if ($scope !== LockScope::ANY) {
            $query .= sprintf(" WHERE scope = %d", $scope);
        }

        // Apply order by column, if column name is valid
        if ($order_by && in_array($order_by, $this->columns, true)) {
            $query .= " ORDER BY {$order_by}";
            if ($order === 'asc') {
                $query .= ' ASC';
            } elseif ($order === 'desc') {
                $query .= ' DESC';
            }
        }

        // Apply limits
        $query .= sprintf(" LIMIT %d, %d", $from, $limit);

        // Execute query
        $results = $this->wpdb->get_results($query, ARRAY_A);

        // Return results
        return is_array($results) ? $results : [];
    }


    /**
     * Fetch all items on blacklist (optionally with given $scope).
     *
     * @param int $scope Blacklist scope [optional].
     * @return array
     */
    public function fetchAll($scope = LockScope::ANY)
    {
        // Prepare query
        $query = "SELECT * FROM {$this->blacklist_table}";
        // Apply scope if given
        if ($scope !== LockScope::ANY) {
            $query .= $this->wpdb->prepare(" WHERE scope = %d", $scope);
        }
        // Execute query
        $results = $this->wpdb->get_results($query);
        // Return results
        return is_array($results) ? $results : [];
    }


    /**
     * Is $ip_address on blacklist with given $scope?
     *
     * @param string $ip_address IP address to check.
     * @param int $scope Blacklist scope.
     * @return bool True, if IP address is on blacklist with given scope.
     */
    public function isLocked($ip_address, $scope)
    {
        // Prepare query
        $query = $this->wpdb->prepare(
            "SELECT release_time FROM {$this->blacklist_table} WHERE scope = %d AND ip_address = %s",
            $scope,
            $ip_address
        );
        // Execute query
        $release_time = $this->wpdb->get_var($query);
        // Evaluate release time
        return is_string($release_time) && (current_time('timestamp') < strtotime($release_time));
    }


    /**
     * Lock access from $ip_address to $scope for $duration seconds because of
     * $reason.
     *
     * @param string $ip_address IP address to lock.
     * @param int $duration
     * @param int $scope
     * @param int $reason
     * @return bool True, if IP address has been locked, false otherwise.
     */
    public function lock($ip_address, $duration, $scope, $reason)
    {
        $now = current_time('timestamp');

        $data = [
            'ban_time'      => date(self::MYSQL_DATETIME_FORMAT, $now),
            'release_time'  => date(self::MYSQL_DATETIME_FORMAT, $now + $duration),
            'reason'        => $reason,
        ];

        $where = [
            'ip_address' => $ip_address,
            'scope' => $scope,
        ];

        // Determine, whether IP needs to be inserted or updated
        if ($this->getId($ip_address, $scope)) {
            // Update
            $result = $this->wpdb->update($this->blacklist_table, $data, $where, ['%s', '%s', '%d'], ['%s', '%d']);
        } else {
            // Insert: merge $data with $where
            $result = $this->wpdb->insert($this->blacklist_table, array_merge($data, $where), ['%s', '%s', '%d', '%s', '%d']);
        }

        return $result !== false;
    }


    /**
     * Remove expired entries from blacklist table.
     *
     * @return bool True on success, false on failure.
     */
    public function prune()
    {
        // Prepare query
        // Note: $wpdb->delete cannot be used as it does not support "<=" comparison)
        $query = $this->wpdb->prepare(
            "DELETE FROM {$this->blacklist_table} WHERE release_time <= %s",
            date(self::MYSQL_DATETIME_FORMAT, current_time('timestamp'))
        );
        // Execute query
        $result = $this->wpdb->query($query);
        // Return result
        return $result !== false;
    }


    /**
     * Remove record with primary key $id.
     *
     * @param int $id
     * @return bool True, if record with $id has been removed, false otherwise.
     */
    public function remove($id)
    {
        // Execute query
        $result = $this->wpdb->delete($this->blacklist_table, ['id' => $id], '%d');
        // Return
        return $result !== false;
    }


    /**
     * Remove records with given primary keys.
     *
     * @param array $ids
     * @return int Number of deleted records.
     */
    public function removeMany(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }
        // Prepare query
        $query = sprintf(
            "DELETE FROM {$this->blacklist_table} WHERE %s",
            implode(' OR ', array_map(function ($id) { return sprintf('id = %d', $id); }, $ids))
        );
        // Execute
        $result = $this->wpdb->query($query);
        // Return int
        return $result ?: 0;
    }


    /**
     * Unlock record with primary key $id. Unlocking sets release date to now.
     *
     * @todo Only unlock really active locks.
     *
     * @param int $id
     * @return bool True, if record with $id has been unlocked, false otherwise.
     */
    public function unlock($id)
    {
        // Execute query
        $result = $this->wpdb->update(
            $this->blacklist_table,
            ['release_time' => date(self::MYSQL_DATETIME_FORMAT, current_time('timestamp'))],
            ['id' => $id],
            '%s',
            '%d'
        );
        // Return
        return $result !== false;
    }


    /**
     * Unlock records with primary keys in $ids array. Unlocking sets release
     * date to now.
     *
     * @todo Only unlock really active locks.
     *
     * @param array $ids
     * @return int Number of unlocked records.
     */
    public function unlockMany(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }
        // Prepare query
        $query = sprintf(
            "UDPATE {$this->blacklist_table} SET release_time = '%s' WHERE %s",
            date(self::MYSQL_DATETIME_FORMAT, current_time('timestamp')),
            implode(' OR ', array_map(function ($id) { return sprintf('id = %d', $id); }, $ids))
        );
        // Execute
        $result = $this->wpdb->query($query);
        // Return int
        return $result ?: 0;
    }


    /**
     * Get primary key (id) for record with given $ip_address and $scope.
     *
     * @param string $ip_address IP address to check.
     * @param int $scope
     * @return int|null
     */
    protected function getId($ip_address, $scope)
    {
        // Prepare query
        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->blacklist_table} WHERE scope = %d AND ip_address = %s",
            $scope,
            $ip_address
        );
        // Execute query
        $result = $this->wpdb->get_var($query);
        // Return result
        return is_null($result) ? $result : intval($result);
    }
}
