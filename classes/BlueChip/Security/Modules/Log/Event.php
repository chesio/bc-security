<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

class Event
{
    const AUTH_BAD_COOKIE = 'auth_bad_cookie';
    const LOGIN_FAILURE = 'login_failure';
    const LOGIN_LOCKOUT = 'login_lockdown';
    const LOGIN_SUCCESSFUL = 'login_success';
    const QUERY_404 = 'query_404';
    const CORE_CHECKSUMS_VERIFICATION_ALERT = 'core_checksums_verification_alert';
    const PLUGIN_CHECKSUMS_VERIFICATION_ALERT = 'plugin_checksums_verification_alert';


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
    protected function __construct(string $id, string $name, string $level, string $message, array $context)
    {
        $this->id = $id;
        $this->name = $name;
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }


    public function getId(): string
    {
        return $this->id;
    }


    public function getName(): string
    {
        return $this->name;
    }


    public function getLevel(): string
    {
        return $this->level;
    }


    public function getMessage(): string
    {
        return $this->message;
    }


    public function getContext(): array
    {
        return $this->context;
    }


    public function hasContext(string $key): bool
    {
        return isset($this->context[$key]);
    }
}
