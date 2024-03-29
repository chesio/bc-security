<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class Query404 extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'query_404';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::INFO;

    /**
     * __('Request URI')
     *
     * @var string Request URI that returned 404 error.
     */
    protected string $request_uri = '';


    public function getName(): string
    {
        return __('404 page', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Main query returned no results (404 page) for request {request_uri}.', 'bc-security');
    }


    public function setRequestUri(string $request_uri): self
    {
        $this->request_uri = $request_uri;
        return $this;
    }
}
