<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class PluginChecksumsVerificationAlert extends Event
{
    /**
     * @var string Static event identificator.
     */
    const ID = 'plugin_checksums_verification_alert';

    /**
     * @var string Event log level.
     */
    const LOG_LEVEL = \Psr\Log\LogLevel::WARNING;

    /**
     * __('Plugin name')
     *
     * @var string Plugin name.
     */
    protected $plugin_name = '';

    /**
     * __('Plugin version')
     *
     * @var string Plugin version.
     */
    protected $plugin_version = '';

    /**
     * __('Modified files')
     *
     * @var array List of modified plugin files found during check.
     */
    protected $modified_files = [];

    /**
     * __('Unknown files')
     *
     * @var array List of unknown files found during check.
     */
    protected $unknown_files = [];


    public function getName(): string
    {
        return __('Plugin checksums verification alert', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Plugin: {plugin_name} (ver. {plugin_version}). Following files have been modified: {modified_files}. Following files are unknown: {unknown_files}.', 'bc-security');
    }


    public function setPluginName(string $plugin_name): self
    {
        $this->plugin_name = $plugin_name;
        return $this;
    }


    public function setPluginVersion(string $plugin_version): self
    {
        $this->plugin_version = $plugin_version;
        return $this;
    }


    public function setModifiedFiles(array $modified_files): self
    {
        $this->modified_files = $modified_files;
        return $this;
    }


    public function setUnknownFiles(array $unknown_files): self
    {
        $this->unknown_files = $unknown_files;
        return $this;
    }
}
