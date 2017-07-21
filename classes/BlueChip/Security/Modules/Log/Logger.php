<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Modules;
use Psr\Log;


/**
 * Simple PSR-3 compliant logger with database backend.
 *
 * @link http://www.php-fig.org/psr/psr-3/
 */
class Logger extends Log\AbstractLogger implements Log\LoggerInterface, Modules\Installable, Modules\Loadable
{
    /** @var string Name of DB table where logs are stored */
    const LOG_TABLE = 'bc_security_log';

    /** @var string Date format accepted by MySQL */
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var string Name of DB table where logs are stored (including table prefix) */
    private $log_table;

    /** @var array List of columns in DB table where logs are stored */
    private $columns;

    /** @var string Remote IP address */
    private $remote_address;

    /** @var \wpdb WordPress database access abstraction object */
    private $wpdb;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     * @param string $remote_address Remote IP address.
     */
    public function __construct(\wpdb $wpdb, $remote_address)
    {
        $this->log_table = $wpdb->prefix . self::LOG_TABLE;
        $this->columns = [
            'id', 'date_and_time', 'ip_address', 'event', 'level', 'message', 'context',
        ];
        $this->remote_address = $remote_address;
        $this->wpdb = $wpdb;
    }


    public function install()
    {
        // To have dbDelta()
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(implode(' ', [
            "CREATE TABLE {$this->log_table} (",
            "id int unsigned NOT NULL AUTO_INCREMENT,",
            "date_and_time datetime NOT NULL,",
            "ip_address char(128) NOT NULL,",
            "event char(64) NOT NULL,",
            "level tinyint(3) NULL,",
            "message text NULL,",
            "context text NULL,",
            "PRIMARY KEY  (id),", // 2 spaces seems to be necessary
            "INDEX event (event)",
            ");",
        ]));
    }


    public function uninstall()
    {
        $this->wpdb->query(sprintf('DROP TABLE IF EXISTS %s', $this->log_table));
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
        add_action(Action::EVENT, [$this, 'logEvent'], 10, 2);
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
        $this->wpdb->insert(
            $this->log_table,
            [
                'date_and_time' => date(self::MYSQL_DATETIME_FORMAT, current_time('timestamp')),
                'ip_address' => isset($context['ip_address']) ? $context['ip_address'] : $this->remote_address, // Allow overriding of IP address.
                'event' => isset($context['event']) ? $context['event'] : '', // Event is optional.
                'level' => $this->translateLogLevel($level),
                'message' => $message,
                'context' => serialize($context),
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
    }


    /**
     * Log event with $event_id and $context.
     *
     * @param string $event_id
     * @param array $context
     */
    public function logEvent($event_id, array $context)
    {
        $event = Event::create($event_id);
        $this->log($event->getLevel(), $event->getMessage(), array_merge(['event' => $event_id], $context));
    }


    /**
     * Return integer code for given log level.
     *
     * @param string $level Log level constant: emergency, alert, critical, error, warning, notice, info or debug.
     * @return mixed Integer code for given log level or null, if unknown level given.
     */
    public function translateLogLevel($level) {
        switch($level) {
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
                _doing_it_wrong(__METHOD__, sprintf('Unknown log level: %s', $level), '0.2.0');
                return null;
        }
    }


    /**
     * Return number of all records in log table.
     *
     * @param mixed $event Only count records under event name (empty string is allowed).
     * @return int
     */
    public function countAll($event = null)
    {
        $query = "SELECT COUNT(id) AS total FROM {$this->log_table}";

        // Event may be empty string as well, therefore do not use empty().
        if (is_string($event)) {
            $query .= $this->wpdb->prepare(' WHERE event = %s', $event);
        }

        return intval($this->wpdb->get_var($query));
    }


    /**
     * Fetch log records that match provided arguments.
     *
     * @param string $event Only fetch records under event name (empty string is allowed).
     * @param int $from
     * @param int $limit
     * @param string $order_by
     * @param string $order
     * @return array
     */
    public function fetch($event = null, $from = 0, $limit = 20, $order_by = null, $order = null)
    {
        // Prepare query
        $query = "SELECT * FROM {$this->log_table}";

        // Event may be empty string as well, therefore do not use empty().
        if (is_string($event)) {
            $query .= $this->wpdb->prepare(" WHERE event = %s", $event);
        }

        // Apply order by column, if column name is valid.
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
     * Return list of distinct event types present in logs table.
     *
     * @todo Probably obsolete.
     *
     * @return array
     */
    public function getEventIds()
    {
        $result = $this->wpdb->get_results("SELECT DISTINCT(event) FROM {$this->log_table}");

        return is_array($result) ? wp_list_pluck($result, 'event') : [];
    }


    /**
     * Return list of distinct IP addresses from which a successful login has
     * been made.
     *
     * @return array
     */
    public function getKnownIps()
    {
        $result = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT DISTINCT(ip_address) FROM {$this->log_table} WHERE event = %s", Event::LOGIN_SUCCESSFUL)
        );

        return is_array($result) ? wp_list_pluck($result, 'ip_address') : [];
    }
}
