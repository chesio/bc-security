<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class CoreChecksumsVerificationAlert extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::CORE_CHECKSUMS_VERIFICATION_ALERT,
            __('Core checksums verification alert', 'bc-security'),
            LogLevel::WARNING,
            __('Following files have been modified: {modified_files}. Following files are unknown: {unknown_files}.', 'bc-security'),
            ['modified_files' => __('Modified files', 'bc-security'), 'unknown_files' => __('Unknown files', 'bc-security')]
        );
    }
}
