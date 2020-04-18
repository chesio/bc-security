<?php

namespace BlueChip\Security\Modules\Log;

/**
 * Actions (meant for do_action() calls) to which logger listens to.
 */
interface Action
{
    /** @var string Log action with event object */
    public const EVENT     = 'bc-security.log.event';
    /** @var string Log action without explicit log level */
    public const LOG       = 'bc-security.log';
    /** @var string Log action with debug log level */
    public const DEBUG     = 'bc-security.log.debug';
    /** @var string Log action with info log level */
    public const INFO      = 'bc-security.log.info';
    /** @var string Log action with notice log level */
    public const NOTICE    = 'bc-security.log.notice';
    /** @var string Log action with warning log level */
    public const WARNING   = 'bc-security.log.warning';
    /** @var string Log action with error log level */
    public const ERROR     = 'bc-security.log.error';
    /** @var string Log action with critical log level */
    public const CRITICAL  = 'bc-security.log.critical';
    /** @var string Log action with alert log level */
    public const ALERT     = 'bc-security.log.alert';
    /** @var string Log action with emergency log level */
    public const EMERGENCY = 'bc-security.log.emergency';
}
