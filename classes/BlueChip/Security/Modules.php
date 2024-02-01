<?php

declare(strict_types=1);

namespace BlueChip\Security;

use BlueChip\Security\Modules\Access\Bouncer as AccessBouncer;
use BlueChip\Security\Modules\BadRequestsBanner\Core as BadRequestsBanner;
use BlueChip\Security\Modules\Checklist\Manager as ChecklistManager;
use BlueChip\Security\Modules\Cron\Manager as CronJobManager;
use BlueChip\Security\Modules\ExternalBlocklist\Manager as ExternalBlocklistManager;
use BlueChip\Security\Modules\Hardening\Core as Hardening;
use BlueChip\Security\Modules\InternalBlocklist\HtaccessSynchronizer;
use BlueChip\Security\Modules\InternalBlocklist\Manager as InternalBlocklistManager;
use BlueChip\Security\Modules\Log\EventsMonitor;
use BlueChip\Security\Modules\Log\Logger;
use BlueChip\Security\Modules\Login\Bookkeeper;
use BlueChip\Security\Modules\Login\Gatekeeper;
use BlueChip\Security\Modules\Notifications\Watchman;
use BlueChip\Security\Modules\Services\ReverseDnsLookup\Resolver as HostnameResolver;
use BlueChip\Security\Setup\GoogleAPI;
use IteratorAggregate;
use Traversable;
use wpdb;

/**
 * Object that provides access to all plugin modules
 *
 * @implements IteratorAggregate<int, object>
 */
class Modules implements IteratorAggregate
{
    private AccessBouncer $access_bouncer;

    private BadRequestsBanner $bad_requests_banner;

    private Bookkeeper $bookkeeper;

    private ChecklistManager $checklist_manager;

    private CronJobManager $cron_job_manager;

    private EventsMonitor $events_monitor;

    private ExternalBlocklistManager $external_blocklist_manager;

    private Gatekeeper $gatekeeper;

    private Hardening $hardening;

    private HtaccessSynchronizer $htaccess_synchronizer;

    private HostnameResolver $hostname_resolver;

    private InternalBlocklistManager $internal_blocklist_manager;

    private Logger $logger;

    private Watchman $notifier;


    public function __construct(wpdb $wpdb, string $remote_address, string $server_address, Settings $settings)
    {
        $google_api = new GoogleAPI($settings->forSetup());

        $this->hostname_resolver            = new HostnameResolver();
        $this->cron_job_manager             = new CronJobManager($settings->forCronJobs());
        $this->logger                       = new Logger($wpdb, $remote_address, $settings->forLog(), $this->hostname_resolver);
        $this->checklist_manager            = new ChecklistManager($settings->forChecklistAutorun(), $this->cron_job_manager, $wpdb, $google_api->getKey());
        $this->events_monitor               = new EventsMonitor($remote_address, $server_address);
        $this->notifier                     = new Watchman($settings->forNotifications(), $remote_address, $this->logger);
        $this->hardening                    = new Hardening($settings->forHardening());
        $this->htaccess_synchronizer        = new HtaccessSynchronizer();
        $this->internal_blocklist_manager   = new InternalBlocklistManager($wpdb, $this->htaccess_synchronizer);
        $this->external_blocklist_manager   = new ExternalBlocklistManager($settings->forExternalBlocklist(), $this->cron_job_manager);
        $this->bad_requests_banner          = new BadRequestsBanner($remote_address, $server_address, $settings->forBadRequestsBanner(), $this->internal_blocklist_manager);
        $this->access_bouncer               = new AccessBouncer($remote_address, $this->internal_blocklist_manager, $this->external_blocklist_manager);
        $this->bookkeeper                   = new Bookkeeper($settings->forLogin(), $wpdb);
        $this->gatekeeper                   = new Gatekeeper($settings->forLogin(), $remote_address, $this->bookkeeper, $this->internal_blocklist_manager, $this->access_bouncer);
    }

    public function getIterator(): Traversable
    {
        foreach ((array) $this as $module) {
            yield $module;
        }
    }

    public function getBadRequestsBanner(): BadRequestsBanner
    {
        return $this->bad_requests_banner;
    }

    public function getChecklistManager(): ChecklistManager
    {
        return $this->checklist_manager;
    }

    public function getCronJobManager(): CronJobManager
    {
        return $this->cron_job_manager;
    }

    public function getEventsMonitor(): EventsMonitor
    {
        return $this->events_monitor;
    }

    public function getExternalBlocklistManager(): ExternalBlocklistManager
    {
        return $this->external_blocklist_manager;
    }

    public function getGatekeeper(): Gatekeeper
    {
        return $this->gatekeeper;
    }

    public function getHardening(): Hardening
    {
        return $this->hardening;
    }

    public function getHtaccessSynchronizer(): HtaccessSynchronizer
    {
        return $this->htaccess_synchronizer;
    }

    public function getInternalBlocklistManager(): InternalBlocklistManager
    {
        return $this->internal_blocklist_manager;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getNotifier(): Watchman
    {
        return $this->notifier;
    }
}
