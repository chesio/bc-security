<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class Query404 extends Event
{
    /**
     * @var string Static event identificator.
     */
    const ID = 'query_404';

    /**
     * @var string Event log level.
     */
    const LOG_LEVEL = \Psr\Log\LogLevel::INFO;

    /**
     * __('Request URI')
     *
     * @var string Request URI that returned 404 error.
     */
    protected $request_uri = '';


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
