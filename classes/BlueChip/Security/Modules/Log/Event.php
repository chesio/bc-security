<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

use Psr\Log\LogLevel;

/**
 * Every event must be constructed using event ID. All other event properties can be inferred from event ID.
 */
class Event
{
    const AUTH_BAD_COOKIE = 'auth_bad_cookie';
    const LOGIN_FAILURE = 'login_failure';
    const LOGIN_LOCKOUT = 'login_lockdown';
    const LOGIN_SUCCESSFUL = 'login_success';
    const QUERY_404 = 'query_404';
    const CHECKSUMS_VERIFICATION_ALERT = 'checksums_verification_alert';


    /**
     * @var string Unique ID
     */
    private $id;

    /**
     * @var string Human readable name
     */
    private $name;

    /**
     * @var string Log level
     */
    private $level;

    /**
     * @var string Log message
     */
    private $message;

    /**
     * @var array Required context keys
     */
    private $context;


    /**
     * Create event instance.
     *
     * @param string $id
     * @param string $name
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function __construct($id, $name, $level, $message, $context)
    {
        $this->id = $id;
        $this->name = $name;
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }


    public function getId()
    {
        return $this->id;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getLevel()
    {
        return $this->level;
    }


    public function getMessage()
    {
        return $this->message;
    }


    public function getContext()
    {
        return $this->context;
    }


    public function hasContext($key)
    {
        return isset($this->context[$key]);
    }


    /**
     * Create event object for given $id.
     *
     * @param string $id Valid event ID.
     * @return \BlueChip\Security\Modules\Log\Event
     */
    public static function create($id)
    {
        switch ($id) {
            case self::AUTH_BAD_COOKIE:
                return new self(
                    $id,
                    __('Bad authentication cookie', 'bc-security'),
                    LogLevel::NOTICE,
                    __('Bad authentication cookie used with {username}.', 'bc-security'),
                    ['username' => __('Username', 'bc-security')]
                );
            case self::LOGIN_FAILURE:
                return new self(
                    $id,
                    __('Failed login', 'bc-security'),
                    LogLevel::NOTICE,
                    __('Login attempt with username {username} failed.', 'bc-security'),
                    ['username' => __('Username', 'bc-security')]
                );
            case self::LOGIN_LOCKOUT:
                return new self(
                    $id,
                    __('Login lockout', 'bc-security'),
                    LogLevel::WARNING,
                    __('Remote IP address {ip_address} has been locked out from login for {duration} seconds. Last username used for login was {username}.', 'bc-security'),
                    ['ip_address' => __('IP Address', 'bc-security'), 'username' => __('Username', 'bc-security'), 'duration' => __('Duration', 'bc-security')]
                );
            case self::LOGIN_SUCCESSFUL:
                return new self(
                    $id,
                    __('Successful login', 'bc-security'),
                    LogLevel::INFO,
                    __('User {username} logged in successfully.', 'bc-security'),
                    ['username' => __('Username', 'bc-security')]
                );
            case self::QUERY_404:
                return new self(
                    $id,
                    __('404 page', 'bc-security'),
                    LogLevel::INFO,
                    __('Main query returned no results (404 page) for request {request}.', 'bc-security'),
                    ['request' => __('Request URI', 'bc-security')]
                );
            case self::CHECKSUMS_VERIFICATION_ALERT:
                return new self(
                    $id,
                    __('Checksums verification alert', 'bc-security'),
                    LogLevel::WARNING,
                    __('Official checksums do not match for the following files: {files}.', 'bc-security'),
                    ['modified_files' => __('Modified files', 'bc-security'), 'unknown_files' => __('Unknown files', 'bc-security')]
                );
            default:
                return null;
        }
    }


    /**
     * Return a list of all declared events.
     *
     * @return array
     */
    public static function enlist()
    {
        return [
            self::AUTH_BAD_COOKIE,
            self::LOGIN_FAILURE,
            self::LOGIN_SUCCESSFUL,
            self::LOGIN_LOCKOUT,
            self::QUERY_404,
            self::CHECKSUMS_VERIFICATION_ALERT,
        ];
    }
}
