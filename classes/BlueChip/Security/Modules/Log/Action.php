<?php

namespace BlueChip\Security\Modules\Log;

/**
 * Actions (meant for do_action() calls) to which logger listens to.
 */
interface Action
{
    /** @var string Log action with event object */
    const EVENT     = 'bc-security.log.event';
    /** @var string Log action without explicit log level */
    const LOG       = 'bc-security.log';
    /** @var string Log action with debug log level */
    const DEBUG     = 'bc-security.log.debug';
    /** @var string Log action with info log level */
    const INFO      = 'bc-security.log.info';
    /** @var string Log action with notice log level */
    const NOTICE    = 'bc-security.log.notice';
    /** @var string Log action with warning log level */
    const WARNING   = 'bc-security.log.warning';
    /** @var string Log action with error log level */
    const ERROR     = 'bc-security.log.error';
    /** @var string Log action with critical log level */
    const CRITICAL  = 'bc-security.log.critical';
    /** @var string Log action with alert log level */
    const ALERT     = 'bc-security.log.alert';
    /** @var string Log action with emergency log level */
    const EMERGENCY = 'bc-security.log.emergency';
}
