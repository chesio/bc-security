<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class CoreChecksumsVerificationAlert extends Event
{
    /**
     * @var string Static event identificator.
     */
    const ID = 'core_checksums_verification_alert';

    /**
     * @var string Event log level.
     */
    const LOG_LEVEL = \Psr\Log\LogLevel::WARNING;

    /**
     * __('Modified files')
     *
     * @var string List of modified core files found during check.
     */
    protected $modified_files = [];

    /**
     * __('Unknown files')
     *
     * @var string List of unknown files found during check.
     */
    protected $unknown_files = [];


    public function getName(): string
    {
        return __('Core checksums verification alert', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Following files have been modified: {modified_files}. Following files are unknown: {unknown_files}.', 'bc-security');
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
