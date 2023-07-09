<?php

namespace BlueChip\Security\Modules\ScannerBlocker;

/**
 * Hooks available in Scanner Blocker security module
 */
interface Hooks
{
    /**
     * Action: triggers when request matches bad request patterns list.
     */
    public const BAD_REQUEST_EVENT = 'bc-security/action:bad-request-event';

    /**
     * Filter: allows to add/remove bad request patterns (filters whatever is stored in plugin settings).
     */
    public const BAD_REQUEST_PATTERNS = 'bc-security/filter:bad-request-patterns';
}
