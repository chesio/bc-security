<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class Query404 extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::QUERY_404,
            __('404 page', 'bc-security'),
            LogLevel::INFO,
            __('Main query returned no results (404 page) for request {request}.', 'bc-security'),
            ['request' => __('Request URI', 'bc-security')]
        );
    }
}
