<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\InternalBlocklist;

use BlueChip\Security\Helpers\MySQLDateTime;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Access\Scope;
use wpdb;

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
class Manager implements Modules\Activable, Modules\Countable, Modules\Installable, Modules\Initializable, \Countable
{
    /**
     * @var string Name of DB table where internal blocklist is stored
     */
    private const BLOCKLIST_TABLE = 'bc_security_internal_blocklist';


    /**
     * @var string Name of cron job action used for background .htaccess file synchronization.
     */
    private const HTACCESS_SYNCHRONIZATION = 'bc-security/synchronize-internal-blocklist-with-htaccess-file';


    /**
     * @var string Name of DB table where blocklist is stored (including table prefix)
     */
    private string $blocklist_table;

    /**
     * @var string[] List of table columns
     */
    private array $columns;


    /**
     * @param wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(private wpdb $wpdb, private HtaccessSynchronizer $htaccess_synchronizer)
    {
        $this->blocklist_table = $wpdb->prefix . self::BLOCKLIST_TABLE;
        $this->columns = [
            'id', 'scope', 'ip_address', 'ban_time', 'release_time', 'reason', 'comment',
        ];
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


    public function activate(): void
    {
        // Do nothing.
    }


    public function deactivate(): void
    {
        // Unschedule all cron jobs consumed by this module.
        wp_unschedule_hook(self::HTACCESS_SYNCHRONIZATION);
    }


    public function init(): void
    {
        // Hook into cron job execution.
        add_action(Modules\Cron\Jobs::INTERNAL_BLOCKLIST_CLEAN_UP, [$this, 'pruneInCron'], 10, 0);
        add_action(self::HTACCESS_SYNCHRONIZATION, [$this, 'runSynchronizationWithHtaccessFile'], 10, 0);
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
     * @param Scope $access_scope
     *
     * @return int
     */
    public function countAll(Scope $access_scope = Scope::ANY): int
    {
        $query = "SELECT COUNT(id) AS total FROM {$this->blocklist_table}";

        if ($access_scope !== Scope::ANY) {
            $query .= $this->wpdb->prepare(" WHERE scope = %d", $access_scope->value);
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
     * @param Scope $access_scope
     * @param int $from
     * @param int $limit
     * @param string $order_by
     * @param string $order
     *
     * @return array<string,mixed>
     */
    public function fetch(Scope $access_scope = Scope::ANY, int $from = 0, int $limit = 20, string $order_by = '', string $order = ''): array
    {
        // Prepare query
        $query = "SELECT * FROM {$this->blocklist_table}";

        // Apply scope if given
        if ($access_scope !== Scope::ANY) {
            $query .= \sprintf(" WHERE scope = %d", $access_scope->value);
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
     * @return string[] List of IP addresses that are currently blocked in website scope.
     */
    public function fetchIpAddressesForHtaccess(): array
    {
        // Prepare query
        $query = sprintf("SELECT ip_address, release_time FROM {$this->blocklist_table} WHERE scope = %d", Scope::WEBSITE->value);

        // Get results.
        $results = $this->wpdb->get_results($query, ARRAY_A);

        $ip_addresses = [];

        foreach ($results as ['ip_address' => $ip_address, 'release_time' => $release_time]) {
            // Filter out-dated records.
            $blocked = \is_string($release_time) && (\time() < MySQLDateTime::parseTimestamp($release_time));

            if ($blocked) {
                $ip_addresses[] = $ip_address;
            }
        }

        // Single IP address may be blocked in the same scope for different reasons, so make sure it is returned only once.
        return \array_unique($ip_addresses);
    }


    /**
     * Is $ip_address on blocklist with given $access_scope?
     *
     * @hook \BlueChip\Security\Modules\InternalBlocklist\Hooks::IS_IP_ADDRESS_LOCKED
     *
     * @param string $ip_address IP address to check.
     * @param Scope $access_scope Access scope.
     *
     * @return bool True if IP address is on blocklist with given access scope.
     */
    public function isLocked(string $ip_address, Scope $access_scope): bool
    {
        // Prepare query. Because of different ban reasons, multiple records may
        // match the where condition, so pick up the most future release time.
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "SELECT MAX(release_time) FROM {$this->blocklist_table} WHERE scope = %d AND ip_address = %s",
            $access_scope->value,
            $ip_address
        );
        // Execute query
        $release_time = $this->wpdb->get_var($query);
        // Evaluate release time
        $result = \is_string($release_time) && (\time() < MySQLDateTime::parseTimestamp($release_time));
        // Allow the result to be filtered
        return apply_filters(Hooks::IS_IP_ADDRESS_LOCKED, $result, $ip_address, $access_scope);
    }


    /**
     * Lock access from $ip_address to $access_scope for $duration seconds because of $reason.
     *
     * @param string $ip_address IP address to lock.
     * @param int $duration
     * @param Scope $access_scope
     * @param BanReason $ban_reason
     * @param string $comment [optional]
     *
     * @return bool True if IP address has been locked, false otherwise.
     */
    public function lock(string $ip_address, int $duration, Scope $access_scope, BanReason $ban_reason, string $comment = ''): bool
    {
        $now = \time();

        $data = [
            'ban_time'      => MySQLDateTime::formatDateTime($now),
            'release_time'  => MySQLDateTime::formatDateTime($now + $duration),
            'comment'       => $comment,
        ];

        $format = ['%s', '%s', '%s'];

        $where = [
            'scope'         => $access_scope->value,
            'ip_address'    => $ip_address,
            'reason'        => $ban_reason->value,
        ];

        $where_format = ['%d', '%s', '%d'];

        // Determine, whether IP needs to be inserted or updated.
        if ($this->getId($ip_address, $access_scope, $ban_reason)) {
            // Update
            $result = $this->wpdb->update($this->blocklist_table, $data, $where, $format, $where_format);
        } else {
            // Insert: merge $data with $where, $format with $where_format.
            $result = $this->wpdb->insert($this->blocklist_table, \array_merge($data, $where), \array_merge($format, $where_format));
        }

        if ($result && ($access_scope === Scope::WEBSITE)) {
            // Trigger immediate synchronization of block rules in .htaccess file...
            $this->synchronizeWithHtaccessFile();
            // ... and schedule synchronization at the release time.
            wp_schedule_single_event($now + $duration, self::HTACCESS_SYNCHRONIZATION);
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
        // Trigger synchronization of block rules in .htaccess file.
        if ($result) {
            $this->synchronizeWithHtaccessFile();
        }
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
            \implode(' OR ', \array_map(fn (int $id): string => \sprintf('id = %d', $id), $ids))
        );
        // Execute query.
        $result = $this->wpdb->query($query);
        // Trigger synchronization of block rules in .htaccess file.
        if ($result) {
            $this->synchronizeWithHtaccessFile();
        }
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
        // Trigger synchronization of block rules in .htaccess file.
        if ($result) {
            $this->synchronizeWithHtaccessFile();
        }
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
            \implode(' OR ', \array_map(fn (int $id): string => \sprintf('id = %d', $id), $ids))
        );
        // Execute query.
        $result = $this->wpdb->query($query);
        // Trigger synchronization of block rules in .htaccess file.
        if ($result) {
            $this->synchronizeWithHtaccessFile();
        }
        // Return number of affected (unlocked) rows.
        return $result ?: 0;
    }


    /**
     * Get primary key (id) for record with given $ip_address, $access_scope and ban $reason.
     * Because of UNIQUE database key restriction, there should be either one or none matching key.
     *
     * @param string $ip_address IP address to check.
     * @param Scope $access_scope
     * @param BanReason $ban_reason
     *
     * @return int|null Record ID or null if no record with given $ip_address, $access_scope and ban $reason exists.
     */
    protected function getId(string $ip_address, Scope $access_scope, BanReason $ban_reason): ?int
    {
        // Prepare query.
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->blocklist_table} WHERE scope = %d AND ip_address = %s AND reason = %d",
            $access_scope->value,
            $ip_address,
            $ban_reason->value
        );
        // Execute query.
        $result = $this->wpdb->get_var($query);
        // Return result.
        return null === $result ? $result : (int) $result;
    }


    public function isHtaccessFileInSync(): bool
    {
        $ip_addresses_to_block = $this->fetchIpAddressesForHtaccess();
        $blocked_ip_addresses = $this->htaccess_synchronizer->extract();

        // Both lists must have the same size...
        if (\count($ip_addresses_to_block) !== \count($blocked_ip_addresses)) {
            return false;
        }

        // ...and the same contents.
        return \array_diff($ip_addresses_to_block, $blocked_ip_addresses) === [];
    }


    /**
     * Synchronize contents of internal blocklist with .htaccess file,
     *
     * @internal This is alias of synchronizeWithHtaccessFile() method that just ignores its return value.
     */
    public function runSynchronizationWithHtaccessFile(): void
    {
        $this->synchronizeWithHtaccessFile();
    }


    /**
     * Synchronize contents of internal blocklist with .htaccess file.
     *
     * @return bool True on success, false on failure.
     */
    public function synchronizeWithHtaccessFile(): bool
    {
        return $this->htaccess_synchronizer->isAvailable()
            ? $this->htaccess_synchronizer->insert($this->fetchIpAddressesForHtaccess())
            : false
        ;
    }
}
