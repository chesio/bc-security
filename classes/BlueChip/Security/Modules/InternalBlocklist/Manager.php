<?php

namespace BlueChip\Security\Modules\InternalBlocklist;

use BlueChip\Security\Helpers\MySQLDateTime;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Access\Scope;

/**
 * Who's on the blocklist, baby?
 *
 * Note on blocklist release time with respect to <, =, > comparisons against
 * current time: item is locked (lock is active) if release time is in the
 * future, in other words: release_time > current_time. Otherwise, the item is
 * not locked (lock is expired).
 *
 * Another important note to make is that single IP address can be blocked
 * several times because of different scope, but also because of different
 * reason. Unlike the scope, the reason is not important for actual application
 * of lock, so practical approach is to use the most restrictive lock (ie. the
 * release date that is the most future one) if single IP is blocked multiple
 * times in the same scope.
 */
class Manager implements Modules\Countable, Modules\Installable, Modules\Initializable, \Countable
{
    /**
     * @var string Name of DB table where internal blocklist is stored
     */
    private const BLOCKLIST_TABLE = 'bc_security_internal_blocklist';


    /**
     * @var string Name of DB table where blocklist is stored (including table prefix)
     */
    private $blocklist_table;

    /**
     * @var string[] List of table columns
     */
    private $columns;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->blocklist_table = $wpdb->prefix . self::BLOCKLIST_TABLE;
        $this->columns = [
            'id', 'scope', 'ip_address', 'ban_time', 'release_time', 'reason', 'comment',
        ];
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
            "CREATE TABLE {$this->blocklist_table} (",
            "id int unsigned NOT NULL AUTO_INCREMENT,",
            "scope tinyint unsigned NOT NULL,",
            "ip_address char(128) NOT NULL,",
            "ban_time datetime NOT NULL,",
            "release_time datetime NOT NULL,",
            "reason tinyint unsigned NOT NULL,",
            "comment char(255) NOT NULL,",
            "PRIMARY KEY  (id),", // 2 spaces seems to be necessary
            "UNIQUE KEY ip_in_scope_for_reason (scope, ip_address, reason)",
            ") $charset_collate;",
        ]));
    }


    public function uninstall(): void
    {
        $this->wpdb->query(\sprintf('DROP TABLE IF EXISTS %s', $this->blocklist_table));
    }


    public function init(): void
    {
        // Hook into cron job execution.
        add_action(Modules\Cron\Jobs::INTERNAL_BLOCKLIST_CLEAN_UP, [$this, 'pruneInCron'], 10, 0);
    }


    /**
     * @internal Implements Countable interface.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->countAll();
    }


    /**
     * Return number of all records on blocklist (active and expired).
     *
     * @internal Implements \BlueChip\Security\Modules\Countable interface.
     *
     * @param int $scope
     *
     * @return int
     */
    public function countAll(int $scope = Scope::ANY): int
    {
        $query = "SELECT COUNT(id) AS total FROM {$this->blocklist_table}";

        if ($scope !== Scope::ANY) {
            $query .= $this->wpdb->prepare(" WHERE scope = %d", $scope);
        }

        return (int) $this->wpdb->get_var($query);
    }


    /**
     * Return number of records inserted since given $timestamp.
     *
     * @internal Implements \BlueChip\Security\Modules\Countable interface.
     *
     * @param int $timestamp
     *
     * @return int
     */
    public function countFrom(int $timestamp): int
    {
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "SELECT COUNT(id) AS total FROM {$this->blocklist_table} WHERE ban_time > %s",
            MySQLDateTime::formatDateTime($timestamp)
        );

        return (int) $this->wpdb->get_var($query);
    }


    /**
     * Fetch all items on blocklist that match provided arguments.
     *
     * @param int $scope
     * @param int $from
     * @param int $limit
     * @param string $order_by
     * @param string $order
     *
     * @return array<int,array<string,string>>
     */
    public function fetch(int $scope = Scope::ANY, int $from = 0, int $limit = 20, string $order_by = '', string $order = ''): array
    {
        // Prepare query
        $query = "SELECT * FROM {$this->blocklist_table}";

        // Apply scope if given
        if ($scope !== Scope::ANY) {
            $query .= \sprintf(" WHERE scope = %d", $scope);
        }

        // Apply order by column if column name is valid
        if ($order_by && \in_array($order_by, $this->columns, true)) {
            $query .= " ORDER BY {$order_by}";
            if ($order === 'asc') {
                $query .= ' ASC';
            } elseif ($order === 'desc') {
                $query .= ' DESC';
            }
        }

        // Apply limits
        $query .= \sprintf(" LIMIT %d, %d", $from, $limit);

        // Execute query
        $results = $this->wpdb->get_results($query, ARRAY_A);

        // Return results
        return \is_array($results) ? $results : [];
    }


    /**
     * Is $ip_address on blocklist with given $scope?
     *
     * @hook \BlueChip\Security\Modules\InternalBlocklist\Hooks::IS_IP_ADDRESS_LOCKED
     *
     * @param string $ip_address IP address to check.
     * @param int $scope Access scope.
     *
     * @return bool True if IP address is on blocklist with given access scope.
     */
    public function isLocked(string $ip_address, int $scope): bool
    {
        // Prepare query. Because of different ban reasons, multiple records may
        // match the where condition, so pick up the most future release time.
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "SELECT MAX(release_time) FROM {$this->blocklist_table} WHERE scope = %d AND ip_address = %s",
            $scope,
            $ip_address
        );
        // Execute query
        $release_time = $this->wpdb->get_var($query);
        // Evaluate release time
        $result = \is_string($release_time) && (\time() < MySQLDateTime::parseTimestamp($release_time));
        // Allow the result to be filtered
        return apply_filters(Hooks::IS_IP_ADDRESS_LOCKED, $result, $ip_address, $scope);
    }


    /**
     * Lock access from $ip_address to $scope for $duration seconds because of $reason.
     *
     * @param string $ip_address IP address to lock.
     * @param int $duration
     * @param int $scope
     * @param int $reason
     * @param string $comment [optional]
     *
     * @return bool True if IP address has been locked, false otherwise.
     */
    public function lock(string $ip_address, int $duration, int $scope, int $reason, string $comment = ''): bool
    {
        $now = \time();

        $data = [
            'ban_time'      => MySQLDateTime::formatDateTime($now),
            'release_time'  => MySQLDateTime::formatDateTime($now + $duration),
            'comment'       => $comment,
        ];

        $format = ['%s', '%s', '%s'];

        $where = [
            'scope'         => $scope,
            'ip_address'    => $ip_address,
            'reason'        => $reason,
        ];

        $where_format = ['%d', '%s', '%d'];

        // Determine, whether IP needs to be inserted or updated.
        if ($this->getId($ip_address, $scope, $reason)) {
            // Update
            $result = $this->wpdb->update($this->blocklist_table, $data, $where, $format, $where_format);
        } else {
            // Insert: merge $data with $where, $format with $where_format.
            $result = $this->wpdb->insert($this->blocklist_table, \array_merge($data, $where), \array_merge($format, $where_format));
        }

        return $result !== false;
    }


    /**
     * Remove expired entries from blocklist table.
     *
     * @return bool True on success, false on failure.
     */
    public function prune(): bool
    {
        // Prepare query
        // Note: $wpdb->delete cannot be used as it does not support "<=" comparison)
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "DELETE FROM {$this->blocklist_table} WHERE release_time <= %s",
            MySQLDateTime::formatDateTime(\time())
        );
        // Execute query
        $result = $this->wpdb->query($query);
        // Return result
        return $result !== false;
    }


    /**
     * @hook \BlueChip\Security\Modules\Cron\Jobs::INTERNAL_BLOCKLIST_CLEAN_UP
     *
     * @internal Runs `prune` method and discards its return value.
     */
    public function pruneInCron(): void
    {
        $this->prune();
    }


    /**
     * Remove record with primary key $id.
     *
     * @param int $id
     *
     * @return bool True if record with $id has been removed, false otherwise.
     */
    public function remove(int $id): bool
    {
        // Execute query.
        $result = $this->wpdb->delete($this->blocklist_table, ['id' => $id], ['%d']);
        // Return status.
        return $result !== false;
    }


    /**
     * Remove records with given primary keys.
     *
     * @param int[] $ids
     *
     * @return int Number of deleted records.
     */
    public function removeMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        // Prepare query.
        $query = \sprintf(
            "DELETE FROM {$this->blocklist_table} WHERE %s",
            \implode(' OR ', \array_map(function ($id) {
                return \sprintf('id = %d', $id);
            }, $ids))
        );
        // Execute query.
        $result = $this->wpdb->query($query);
        // Return number of affected (unlocked) rows.
        return $result ?: 0;
    }


    /**
     * Unlock record with primary key $id. Unlocking sets release date to now.
     *
     * @todo Only unlock really active locks.
     *
     * @param int $id
     *
     * @return bool True if record with $id has been unlocked, false otherwise.
     */
    public function unlock(int $id): bool
    {
        // Execute query.
        $result = $this->wpdb->update(
            $this->blocklist_table,
            ['release_time' => MySQLDateTime::formatDateTime(\time())],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
        // Return status.
        return $result !== false;
    }


    /**
     * Unlock records with primary keys in $ids array. Unlocking sets release date to now.
     *
     * @todo Only unlock really active locks.
     *
     * @param int[] $ids
     *
     * @return int Number of unlocked records.
     */
    public function unlockMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        // Prepare query.
        $query = \sprintf(
            "UDPATE {$this->blocklist_table} SET release_time = '%s' WHERE %s",
            MySQLDateTime::formatDateTime(\time()),
            \implode(' OR ', \array_map(function ($id) {
                return \sprintf('id = %d', $id);
            }, $ids))
        );
        // Execute query.
        $result = $this->wpdb->query($query);
        // Return number of affected (unlocked) rows.
        return $result ?: 0;
    }


    /**
     * Get primary key (id) for record with given $ip_address, $scope and ban $reason.
     * Because of UNIQUE database key restriction, there should be either one or none matching key.
     *
     * @param string $ip_address IP address to check.
     * @param int $scope
     * @param int $reason
     *
     * @return int|null Record ID or null if no record with given $ip_address, $scope and ban $reason exists.
     */
    protected function getId(string $ip_address, int $scope, int $reason): ?int
    {
        // Prepare query.
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->blocklist_table} WHERE scope = %d AND ip_address = %s AND reason = %d",
            $scope,
            $ip_address,
            $reason
        );
        // Execute query.
        $result = $this->wpdb->get_var($query);
        // Return result.
        return null === $result ? $result : (int) $result;
    }
}