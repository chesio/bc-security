<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class BadRequestBan extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'bad_request_ban';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::WARNING;

    /**
     * __('Pattern')
     *
     * @var string Bad request pattern that matched request URI.
     */
    protected string $pattern = '';

    /**
     * __('Request URI')
     *
     * @var string Request URI that resulted in ban.
     */
    protected string $request_uri = '';

    /**
     * __('IP Address')
     *
     * @var string Remote IP address.
     */
    protected string $ip_address = '';


    public function getName(): string
    {
        return __('Bad request ban', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Request {request_uri} from {ip_address} resulted in 404 error and matched bad request pattern {pattern}.', 'bc-security');
    }


    public function setIpAddress(string $ip_address): self
    {
        $this->ip_address = $ip_address;
        return $this;
    }


    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }


    public function setRequestUri(string $request_uri): self
    {
        $this->request_uri = $request_uri;
        return $this;
    }
}
