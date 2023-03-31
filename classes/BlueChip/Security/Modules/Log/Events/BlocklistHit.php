<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\ExternalBlocklist\Source;
use BlueChip\Security\Modules\Log\Event;

/**
 * Event triggered when external or internal blocklist is hit.
 */
class BlocklistHit extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'blocklist_hit';

    /**
     * @var string Human-readable label for external blocklist type.
     */
    private const BLOCKLIST_TYPE_EXTERNAL = 'external blocklist';

    /**
     * @var string Human-readable label for internal blocklist type.
     */
    private const BLOCKLIST_TYPE_INTERNAL = 'internal blocklist';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::NOTICE;

    /**
     * __('Blocklist type')
     *
     * @var string Type of blocklist (internal or external) that has the blocked IP address in it.
     */
    protected string $blocklist_type = self::BLOCKLIST_TYPE_INTERNAL;

    /**
     * __('Request type')
     *
     * @var string Type of request that resulted in blocklist hit.
     */
    protected string $request_type = '';

    /**
     * __('IP address')
     *
     * @var string IP address the blocked request originated at.
     */
    protected string $ip_address = '';

    /**
     * __('Blocklist source')
     *
     * @var string Blocklist source
     */
    protected string $source = '';


    public function getName(): string
    {
        return __('Blocklist hit', 'bc-security');
    }


    public function getMessage(): string
    {
        return ($this->blocklist_type === self::BLOCKLIST_TYPE_EXTERNAL)
            ? __('{request_type} from IP address {ip_address} has been blocked by external blocklist based on {source}.', 'bc-security')
            : __('{request_type} from IP address {ip_address} has been blocked by internal blocklist.', 'bc-security')
        ;
    }


    /**
     * Set request type based on given blocklist access scope.
     *
     * @param int $access_scope
     *
     * @return self
     */
    public function setRequestType(int $access_scope): self
    {
        $this->request_type = ucfirst($this->explainAccessScope($access_scope));
        return $this;
    }


    /**
     * Set IP address the blocked request originated at.
     *
     * @param string $ip_address
     *
     * @return self
     */
    public function setIpAddress(string $ip_address): self
    {
        $this->ip_address = $ip_address;
        return $this;
    }


    /**
     * Set source behind blocklist entry that resulted in blocklist hit. Also mark hit as originating from external blocklist.
     *
     * @param Source $source
     *
     * @return self
     */
    public function setSource(Source $source): self
    {
        $this->blocklist_type = self::BLOCKLIST_TYPE_EXTERNAL;
        $this->source = $source->getTitle();
        return $this;
    }


    /**
     * Translate $access_scope value into human-readable request type.
     */
    private function explainAccessScope(int $access_scope): string
    {
        switch ($access_scope) {
            case Scope::ADMIN:
                return 'login request';
            case Scope::COMMENTS:
                return 'comment request';
            case Scope::WEBSITE:
                return 'website request';
            default:
                return 'unknown request';
        }
    }
}
