<?php

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Helpers\MySQLDateTime;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Services\ReverseDnsLookup;
use Psr\Log;

/**
 * Simple PSR-3 compliant logger with database backend.
 *
 * @link http://www.php-fig.org/psr/psr-3/
 */
class Logger extends Log\AbstractLogger implements Log\LoggerInterface, Modules\Countable, Modules\Installable, Modules\Loadable, Modules\Initializable, \Countable
{
    /** @var string Name of DB table where logs are stored */
    private const LOG_TABLE = 'bc_security_log';


    /** @var string Name of DB table where logs are stored (including table prefix) */
    private $log_table;

    /** @var array List of columns in DB table where logs are stored */
    private $columns;

    /** @var string Remote IP address */
    private $remote_address;

    /** @var \BlueChip\Security\Modules\Log\Settings Module settings */
    private $settings;

    /** @var \BlueChip\Security\Modules\Services\ReverseDnsLookup\Resolver */
    private $hostname_resolver;

    /** @var \wpdb WordPress database access abstraction object */
    private $wpdb;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     * @param string $remote_address Remote IP address.
     * @param \BlueChip\Security\Modules\Log\Settings $settings Module settings.
     * @param \BlueChip\Security\Modules\Services\ReverseDnsLookup\Resolver $hostname_resolver
     */
    public function __construct(\wpdb $wpdb, string $remote_address, Settings $settings, ReverseDnsLookup\Resolver $hostname_resolver)
    {
        $this->log_table = $wpdb->prefix . self::LOG_TABLE;
        $this->columns = [
            'id', 'date_and_time', 'ip_address', 'hostname', 'event', 'level', 'message', 'context',
        ];
        $this->remote_address = $remote_address;
        $this->settings = $settings;
        $this->hostname_resolver = $hostname_resolver;
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


    public function uninstall()
    {
        $this->wpdb->query(\sprintf('DROP TABLE IF EXISTS %s', $this->log_table));
    }


    public function load()
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


    public function init()
    {
        // Hook into cron job execution.
        add_action(Modules\Cron\Jobs::LOGS_CLEAN_UP_BY_AGE, [$this, 'pruneByAge'], 10, 0);
        add_action(Modules\Cron\Jobs::LOGS_CLEAN_UP_BY_SIZE, [$this, 'pruneBySize'], 10, 0);
        // Hook into reverse DNS lookup.
        add_action(Hooks::HOSTNAME_RESOLVED, [$this, 'processReverseDnsLookupResponse'], 10, 1);
    }


    /**
     * Log generic event.
     *
     * @param string $level
     * @param string $message
     * @param array $context
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
     * @param \BlueChip\Security\Modules\Log\Event $event
     */
    public function logEvent(Event $event)
    {
        // Include event ID in context.
        $this->log($event->getLogLevel(), $event->getMessage(), \array_merge(['event' => $event->getId()], $event->getContext()));
    }


    /**
     * Return integer code for given log level.
     *
     * @param string $level Log level constant: emergency, alert, critical, error, warning, notice, info or debug.
     * @return int|null Integer code for given log level or null, if unknown level given.
     */
    public function translateLogLevel(string $level): ?int
    {
        switch ($level) {
            case Log\LogLevel::EMERGENCY:
                return 0;
            case Log\LogLevel::ALERT:
                return 1;
            case Log\LogLevel::CRITICAL:
                return 2;
            case Log\LogLevel::ERROR:
                return 3;
            case Log\LogLevel::WARNING:
                return 4;
            case Log\LogLevel::NOTICE:
                return 5;
            case Log\LogLevel::INFO:
                return 6;
            case Log\LogLevel::DEBUG:
                return 7;
            default:
                _doing_it_wrong(__METHOD__, \sprintf('Unknown log level: %s', $level), '0.2.0');
                return null;
        }
    }


    /**
     * Process response from (non-blocking) reverse DNS lookup - update hostname of record with resolved IP address.
     *
     * @param \BlueChip\Security\Modules\Services\ReverseDnsLookup\Response $response
     */
    public function processReverseDnsLookupResponse(ReverseDnsLookup\Response $response)
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
     * @return int
     */
    public function countFrom(int $timestamp): int
    {
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
     * @return array
     */
    public function fetch(?string $event = null, int $from = 0, int $limit = 20, string $order_by = '', string $order = ''): array
    {
        // Prepare query
        $query = "SELECT * FROM {$this->log_table}";

        // Event may be empty string as well, therefore do not use empty().
        if (\is_string($event)) {
            $query .= $this->wpdb->prepare(" WHERE event = %s", $event);
        }

        // Apply order by column, if column name is valid.
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
     * @return array
     */
    public function getKnownIps(): array
    {
        $result = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT DISTINCT(ip_address) FROM {$this->log_table} WHERE event = %s", Events\LoginSuccessful::ID)
        );

        return \is_array($result) ? wp_list_pluck($result, 'ip_address') : [];
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
        $query = $this->wpdb->prepare(
            "DELETE FROM {$this->log_table} WHERE date_and_time <= %s",
            MySQLDateTime::formatDateTime(\time() - $max_age)
        );
        // Execute query and return true/false status.
        return $this->wpdb->query($query) !== false;
    }


    /**
     * Remove all but configured number of recent records from the table.
     *
     * @return bool True on success, false on failure.
     */
    public function pruneBySize(): bool
    {
        $max_size = $this->settings->getMaxSize();

        // First check, if pruning makes sense at all.
        if ($this->countAll() <= $max_size) {
            return true;
        }

        // Find the biggest ID from all records that should be pruned.
        $query_id = $this->wpdb->prepare("SELECT id FROM {$this->log_table} ORDER BY id DESC LIMIT %d, 1", $max_size);
        if (empty($id = (int) $this->wpdb->get_var($query_id))) {
            return false;
        }

        // Note: $wpdb->delete cannot be used as it does not support "<=" comparison)
        $query = $this->wpdb->prepare("DELETE FROM {$this->log_table} WHERE id <= %d", $id);
        // Execute query and return true/false status.
        return $this->wpdb->query($query) !== false;
    }
}
