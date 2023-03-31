<?php

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Helpers\MySQLDateTime;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Cron\Jobs as CronJobs;
use BlueChip\Security\Modules\Services\ReverseDnsLookup\Resolver;
use BlueChip\Security\Modules\Services\ReverseDnsLookup\Response;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use wpdb;

/**
 * Simple PSR-3 compliant logger with database backend.
 *
 * @link http://www.php-fig.org/psr/psr-3/
 */
class Logger extends AbstractLogger implements LoggerInterface, Modules\Countable, Modules\Installable, Modules\Loadable, Modules\Initializable, \Countable
{
    /**
     * @var string Name of DB table where logs are stored
     */
    private const LOG_TABLE = 'bc_security_log';


    /**
     * @var string Name of DB table where logs are stored (including table prefix)
     */
    private string $log_table;

    /**
     * @var string[] List of columns in DB table where logs are stored
     */
    private array $columns;


    /**
     * @param wpdb $wpdb WordPress database access abstraction object
     * @param string $remote_address Remote IP address.
     * @param Settings $settings Module settings.
     * @param Resolver $hostname_resolver
     */
    public function __construct(private wpdb $wpdb, private string $remote_address, private Settings $settings, private Resolver $hostname_resolver)
    {
        $this->log_table = $wpdb->prefix . self::LOG_TABLE;
        $this->columns = [
            'id', 'date_and_time', 'ip_address', 'hostname', 'event', 'level', 'message', 'context',
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
            "CREATE TABLE {$this->log_table} (",
            "id int unsigned NOT NULL AUTO_INCREMENT,",
            "date_and_time datetime NOT NULL,",
            "ip_address char(128) NOT NULL,",
            "hostname char(255) NOT NULL,",
            "event char(64) NOT NULL,",
            "level tinyint(3) NULL,",
            "message text NULL,",
            "context text NULL,",
            "PRIMARY KEY  (id),", // 2 spaces seems to be necessary
            "INDEX event (event)",
            ") $charset_collate;",
        ]));
    }


    public function uninstall(): void
    {
        $this->wpdb->query(\sprintf('DROP TABLE IF EXISTS %s', $this->log_table));
    }


    public function load(): void
    {
        // Expose log methods via do_action() - inspired by Wonolog:
        // https://github.com/inpsyde/Wonolog/blob/master/docs/02-basic-wonolog-concepts.md#level-rich-log-hooks
        add_action(Action::EMERGENCY, [$this, 'emergency'], 10, 2);
        add_action(Action::ALERT, [$this, 'alert'], 10, 2);
        add_action(Action::CRITICAL, [$this, 'critical'], 10, 2);
        add_action(Action::ERROR, [$this, 'error'], 10, 2);
        add_action(Action::WARNING, [$this, 'warning'], 10, 2);
        add_action(Action::NOTICE, [$this, 'notice'], 10, 2);
        add_action(Action::INFO, [$this, 'info'], 10, 2);
        add_action(Action::DEBUG, [$this, 'debug'], 10, 2);
        add_action(Action::LOG, [$this, 'log'], 10, 3);
        add_action(Action::EVENT, [$this, 'logEvent'], 10, 1);
    }


    public function init(): void
    {
        // Hook into cron job execution.
        add_action(CronJobs::LOGS_CLEAN_UP_BY_AGE, [$this, 'pruneByAgeInCron'], 10, 0);
        add_action(CronJobs::LOGS_CLEAN_UP_BY_SIZE, [$this, 'pruneBySizeInCron'], 10, 0);
        // Hook into reverse DNS lookup.
        add_action(Hooks::HOSTNAME_RESOLVED, [$this, 'processReverseDnsLookupResponse'], 10, 1);
    }


    /**
     * Log generic event.
     *
     * @param string $level
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function log($level, $message, array $context = [])
    {
        // Allow overriding of IP address via $context.
        $ip_address = $context['ip_address'] ?? $this->remote_address;
        // Event is optional, though it makes little sense to log data without event type in the moment.
        $event = $context['event'] ?? '';

        $insertion_status = $this->wpdb->insert(
            $this->log_table,
            [
                'date_and_time' => MySQLDateTime::formatDateTime(\time()),
                'ip_address' => $ip_address,
                'event' => $event,
                'level' => $this->translateLogLevel($level),
                'message' => $message,
                'context' => \serialize($context),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d', // level is represented with integer code
                '%s',
                '%s',
            ]
        );

        if ($insertion_status === 1) {
            // Determine, whether hostname of remote IP address should be immediately resolved.
            $events_with_hostname_resolving = apply_filters(
                Hooks::EVENTS_WITH_HOSTNAME_RESOLUTION,
                [Events\AuthBadCookie::ID, Events\LoginFailure::ID, Events\LoginLockout::ID, Events\LoginSuccessful::ID,]
            );

            if (\in_array($event, $events_with_hostname_resolving, true)) {
                // Schedule hostname resolution for inserted log record.
                $this->hostname_resolver->resolveHostnameInBackground(
                    $ip_address,
                    Hooks::HOSTNAME_RESOLVED,
                    ['log_record_id' => $this->wpdb->insert_id,]
                );
            }
        }
    }


    /**
     * Log $event.
     *
     * @param Event $event
     */
    public function logEvent(Event $event): void
    {
        // Include event ID in context.
        $this->log($event->getLogLevel(), $event->getMessage(), \array_merge(['event' => $event->getId()], $event->getContext()));
    }


    /**
     * Return integer code for given log level.
     *
     * @param string $level Log level constant: emergency, alert, critical, error, warning, notice, info or debug.
     *
     * @return int|null Integer code for given log level or null if unknown level given.
     */
    public function translateLogLevel(string $level): ?int
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
                return 0;
            case LogLevel::ALERT:
                return 1;
            case LogLevel::CRITICAL:
                return 2;
            case LogLevel::ERROR:
                return 3;
            case LogLevel::WARNING:
                return 4;
            case LogLevel::NOTICE:
                return 5;
            case LogLevel::INFO:
                return 6;
            case LogLevel::DEBUG:
                return 7;
            default:
                _doing_it_wrong(__METHOD__, \sprintf('Unknown log level: %s', $level), '0.2.0');
                return null;
        }
    }


    /**
     * Process response from (non-blocking) reverse DNS lookup - update hostname of record with resolved IP address.
     *
     * @param Response $response
     */
    public function processReverseDnsLookupResponse(Response $response): void
    {
        $this->wpdb->update(
            $this->log_table,
            ['hostname' => $response->getHostname()],
            ['id' => $response->getContext()['log_record_id'], 'ip_address' => $response->getIpAddress()],
            ['%s'],
            ['%d', '%s']
        );
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
     * Return number of all records in log table.
     *
     * @internal Implements \BlueChip\Security\Modules\Countable interface.
     *
     * @param string|null $event Only count records under event name (empty string is allowed).
     *
     * @return int
     */
    public function countAll(?string $event = null): int
    {
        $query = "SELECT COUNT(id) AS total FROM {$this->log_table}";

        // Event may be empty string as well, therefore do not use empty().
        if (\is_string($event)) {
            $query .= $this->wpdb->prepare(' WHERE event = %s', $event);
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
            "SELECT COUNT(id) AS total FROM {$this->log_table} WHERE date_and_time > %s",
            MySQLDateTime::formatDateTime($timestamp)
        );

        return (int) $this->wpdb->get_var($query);
    }


    /**
     * Fetch log records that match provided arguments.
     *
     * @param string|null $event Only fetch records under event name (empty string is allowed).
     * @param int $from [optional] Zero-based index for first record to be returned. Default value is 0.
     * @param int $limit [optional] Maximum number of items to be returned. Default value is 20.
     * @param string $order_by [optional] Column name to order the records by.
     * @param string $order [optional] Order direction, either "asc" or "desc".
     *
     * @return array<int,array<string,mixed>>
     */
    public function fetch(?string $event = null, int $from = 0, int $limit = 20, string $order_by = '', string $order = ''): array
    {
        // Prepare query
        $query = "SELECT * FROM {$this->log_table}";

        // Event may be empty string as well, therefore do not use empty().
        if (\is_string($event)) {
            $query .= $this->wpdb->prepare(" WHERE event = %s", $event);
        }

        // Apply order by column if column name is valid.
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
     * Return list of distinct IP addresses from which a successful login has been made.
     *
     * @return string[]
     */
    public function getKnownIps(): array
    {
        /** @var string $query */
        $query = $this->wpdb->prepare("SELECT DISTINCT(ip_address) FROM {$this->log_table} WHERE event = %s", Events\LoginSuccessful::ID);

        $result = $this->wpdb->get_results($query, ARRAY_A);

        return \is_array($result) ? \array_column($result, 'ip_address') : [];
    }


    /**
     * Remove all log records (truncate logs table).
     *
     * @return bool True on success, false on failure.
     */
    public function pruneAll(): bool
    {
        return $this->wpdb->query("TRUNCATE {$this->log_table}") !== false;
    }


    /**
     * Remove all log records that are older than configured maximum age.
     *
     * @return bool True on success, false on failure.
     */
    public function pruneByAge(): bool
    {
        $max_age = $this->settings->getMaxAge();

        // Note: $wpdb->delete cannot be used as it does not support "<=" comparison)
        /** @var string $query */
        $query = $this->wpdb->prepare(
            "DELETE FROM {$this->log_table} WHERE date_and_time <= %s",
            MySQLDateTime::formatDateTime(\time() - $max_age)
        );
        // Execute query and return true/false status.
        return $this->wpdb->query($query) !== false;
    }


    /**
     * @hook \BlueChip\Security\Modules\Cron\Jobs::LOGS_CLEAN_UP_BY_AGE
     *
     * @internal Runs `pruneByAge` method and discards its return value.
     */
    public function pruneByAgeInCron(): void
    {
        $this->pruneByAge();
    }


    /**
     * Remove all but configured number of recent records from the table.
     *
     * @return bool True on success, false on failure.
     */
    public function pruneBySize(): bool
    {
        $max_size = $this->settings->getMaxSize();

        // First check if pruning makes sense at all.
        if ($this->countAll() <= $max_size) {
            return true;
        }

        // Find the biggest ID from all records that should be pruned.
        /** @var string $query_id */
        $query_id = $this->wpdb->prepare("SELECT id FROM {$this->log_table} ORDER BY id DESC LIMIT %d, 1", $max_size);
        if (empty($id = (int) $this->wpdb->get_var($query_id))) {
            return false;
        }

        // Note: $wpdb->delete cannot be used as it does not support "<=" comparison)
        /** @var string $query */
        $query = $this->wpdb->prepare("DELETE FROM {$this->log_table} WHERE id <= %d", $id);
        // Execute query and return true/false status.
        return $this->wpdb->query($query) !== false;
    }


    /**
     * @hook \BlueChip\Security\Modules\Cron\Jobs::LOGS_CLEAN_UP_BY_SIZE
     *
     * @internal Runs `pruneBySize` method and discards its return value.
     */
    public function pruneBySizeInCron(): void
    {
        $this->pruneBySize();
    }
}
