<?php

namespace BlueChip\Security;

use BlueChip\Security\Modules\Checklist\AutorunSettings as ChecklistAutorunSettings;
use BlueChip\Security\Modules\Cron\Settings as CronSettings;
use BlueChip\Security\Modules\ExternalBlocklist\Settings as ExternalBlocklistSettings;
use BlueChip\Security\Modules\Hardening\Settings as HardeningSettings;
use BlueChip\Security\Modules\Log\Settings as LogSettings;
use BlueChip\Security\Modules\Login\Settings as LoginSettings;
use BlueChip\Security\Modules\Notifications\Settings as NotificationsSettings;
use BlueChip\Security\Modules\BadRequestsBanner\Settings as BadRequestsBannerSettings;
use BlueChip\Security\Setup\Settings as SetupSettings;

/**
 * Object that provides access to all plugin settings
 */
class Settings implements \IteratorAggregate
{
    private ChecklistAutorunSettings $checklist_autorun;

    private CronSettings $cron_jobs;

    private ExternalBlocklistSettings $external_blocklist;

    private HardeningSettings $hardening;

    private LogSettings $log;

    private LoginSettings $login;

    private NotificationsSettings $notifications;

    private BadRequestsBannerSettings $bad_requests_banner;

    private SetupSettings $setup;


    public function __construct()
    {
        $this->checklist_autorun    = new ChecklistAutorunSettings('bc-security-checklist-autorun');
        $this->cron_jobs            = new CronSettings('bc-security-cron-jobs');
        $this->external_blocklist   = new ExternalBlocklistSettings('bc-security-external-blocklist');
        $this->hardening            = new HardeningSettings('bc-security-hardening');
        $this->log                  = new LogSettings('bc-security-log');
        $this->login                = new LoginSettings('bc-security-login');
        $this->notifications        = new NotificationsSettings('bc-security-notifications');
        $this->bad_requests_banner  = new BadRequestsBannerSettings('bc-security-bad-requests-banner');
        $this->setup                = new SetupSettings('bc-security-setup');
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator((array) $this);
    }

    public function forChecklistAutorun(): ChecklistAutorunSettings
    {
        return $this->checklist_autorun;
    }

    public function forCronJobs(): CronSettings
    {
        return $this->cron_jobs;
    }

    public function forExternalBlocklist(): ExternalBlocklistSettings
    {
        return $this->external_blocklist;
    }

    public function forHardening(): HardeningSettings
    {
        return $this->hardening;
    }

    public function forLog(): LogSettings
    {
        return $this->log;
    }

    public function forLogin(): LoginSettings
    {
        return $this->login;
    }

    public function forNotifications(): NotificationsSettings
    {
        return $this->notifications;
    }

    public function forBadRequestsBanner(): BadRequestsBannerSettings
    {
        return $this->bad_requests_banner;
    }

    public function forSetup(): SetupSettings
    {
        return $this->setup;
    }
}
