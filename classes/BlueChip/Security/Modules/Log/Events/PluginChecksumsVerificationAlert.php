<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class PluginChecksumsVerificationAlert extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::PLUGIN_CHECKSUMS_VERIFICATION_ALERT,
            __('Plugin checksums verification alert', 'bc-security'),
            LogLevel::WARNING,
            __('Plugin: {plugin_name} (ver. {plugin_version}). Following files have been modified: {modified_files}. Following files are unknown: {unknown_files}.', 'bc-security'),
            ['plugin_name' => __('Plugin name', 'bc-security'), 'plugin_version' => __('Plugin version', 'bc-security'), 'modified_files' => __('Modified files', 'bc-security'), 'unknown_files' => __('Unknown files', 'bc-security')]
        );
    }
}
