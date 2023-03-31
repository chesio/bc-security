<?php

namespace BlueChip\Security;

/**
 * Object that provides access to all plugin settings
 */
class Settings implements \IteratorAggregate
{
    /**
     * @var Modules\Checklist\AutorunSettings
     */
    private $checklist_autorun;

    /**
     * @var Modules\Cron\Settings
     */
    private $cron_jobs;

    /**
     * @var Modules\ExternalBlocklist\Settings
     */
    private $external_blocklist;

    /**
     * @var Modules\Hardening\Settings
     */
    private $hardening;

    /**
     * @var Modules\Log\Settings
     */
    private $log;

    /**
     * @var Modules\Login\Settings
     */
    private $login;

    /**
     * @var Modules\Notifications\Settings
     */
    private $notifications;

    /**
     * @var Setup\Settings
     */
    private $setup;


    public function __construct()
    {
        $this->checklist_autorun    = new Modules\Checklist\AutorunSettings('bc-security-checklist-autorun');
        $this->cron_jobs            = new Modules\Cron\Settings('bc-security-cron-jobs');
        $this->external_blocklist   = new Modules\ExternalBlocklist\Settings('bc-security-external-blocklist');
        $this->hardening            = new Modules\Hardening\Settings('bc-security-hardening');
        $this->log                  = new Modules\Log\Settings('bc-security-log');
        $this->login                = new Modules\Login\Settings('bc-security-login');
        $this->notifications        = new Modules\Notifications\Settings('bc-security-notifications');
        $this->setup                = new Setup\Settings('bc-security-setup');
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator((array) $this);
    }

    public function forChecklistAutorun(): Modules\Checklist\AutorunSettings
    {
        return $this->checklist_autorun;
    }

    public function forCronJobs(): Modules\Cron\Settings
    {
        return $this->cron_jobs;
    }

    public function forExternalBlocklist(): Modules\ExternalBlocklist\Settings
    {
        return $this->external_blocklist;
    }

    public function forHardening(): Modules\Hardening\Settings
    {
        return $this->hardening;
    }

    public function forLog(): Modules\Log\Settings
    {
        return $this->log;
    }

    public function forLogin(): Modules\Login\Settings
    {
        return $this->login;
    }

    public function forNotifications(): Modules\Notifications\Settings
    {
        return $this->notifications;
    }

    public function forSetup(): Setup\Settings
    {
        return $this->setup;
    }
}
