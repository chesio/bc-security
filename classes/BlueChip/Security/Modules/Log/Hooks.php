<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

interface Hooks
{
    /**
     * Filter: allows to skip logging of 404 events.
     *
     * // Skip logging of non-existent autodiscover/autodiscover.xml file.
     * add_filter(\BlueChip\Security\Modules\Log\Hooks::LOG_404_EVENT, function (bool $do_log, string $request_uri) {
     *     return !in_array($request_uri, ['autodiscover/autodiscover.xml']);
     * }, 10, 2);
     */
    const LOG_404_EVENT = 'bc-security/filter:log-404-event';
}
