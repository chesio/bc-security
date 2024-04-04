<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Helpers\Transients;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\Cron\Jobs;
use BlueChip\Security\Modules\Cron\Manager as CronManager;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;
use BlueChip\Security\Modules\Initializable;
use InvalidArgumentException;

class Manager implements Initializable
{
    /**
     * @var string[] List of available sources for external blocklists.
     */
    private const SOURCES = [
        AmazonWebServices::class,
    ];

    /**
     * @var string Key used as base for storing IP ranges for external blocklist sources.
     */
    private const TRANSIENT_KEY = 'external-blocklist-source';

    /**
     * @var array<int, Blocklist|null> List of external blocklists per access scope (lazy-loaded).
     */
    private $blocklists = [];

    /**
     * @var array<string,Source> List of sources.
     */
    private $sources = [];


    public function __construct(private Settings $settings, private CronManager $cron_manager)
    {
        foreach (Scope::cases() as $access_scope) {
            if ($access_scope !== Scope::ANY) {
                $this->blocklists[$access_scope->value] = null;
            }
        }

        foreach (self::SOURCES as $class) {
            $this->sources[$class] = new $class();
        }
    }


    public function init(): void
    {
        // Whenever list of enabled sources changes, update local cache and enable or disable related cron job.
        $this->settings->addUpdateHook($this->updateLocalCacheState(...));

        // Hook to action triggered by cron job (and integration tests).
        add_action(Jobs::EXTERNAL_BLOCKLIST_REFRESH, $this->refreshSources(...), 10, 0);
    }


    /**
     * Get blocklist for given $access_scope.
     *
     * @internal Lazy-loads blocklists and IP ranges for sources assigned to the blocklists.
     */
    public function getBlocklist(Scope $access_scope): Blocklist
    {
        // Blocklist for non-scope should not be requested.
        if ($access_scope === Scope::ANY) {
            throw new InvalidArgumentException("Cannot get blocklist for unspecified access scope!");
        }

        // Lazy load the blocklist for given access scope if necessary.
        if ($this->blocklists[$access_scope->value] === null) {
            $blocklist = new Blocklist();

            foreach ($this->sources as $class => $source) {
                if ($this->settings->getAccessScope($class) === $access_scope) {
                    // Read IP ranges from local cache.
                    $this->populate($source);
                    // Add source to blacklist.
                    $blocklist->addSource($source);
                }
            }

            $this->blocklists[$access_scope->value] = $blocklist;
        }

        return $this->blocklists[$access_scope->value];
    }


    /**
     * Get source instance for given $class with IP ranges populated from local cache.
     */
    public function getSource(string $class): Source
    {
        $source = $this->sources[$class];

        $this->populate($source);

        return $source;
    }


    /**
     * Is $ip_address on any external blocklist with given $access_scope?
     *
     * @param string $ip_address IP address to check.
     * @param Scope $access_scope Access scope to check.
     *
     * @return bool True if IP address is on blocklist with given access scope, false otherwise.
     */
    public function isBlocked(string $ip_address, Scope $access_scope): bool
    {
        return $this->getBlocklist($access_scope)->getSource($ip_address) !== null;
    }


    /**
     * Warm up or tear down blocklist sources depending on their activation status.
     * Also activate or deactivate cron job for blocklist refresh.
     */
    private function updateLocalCacheState(): void
    {
        $cron_job_required = false;

        foreach ($this->sources as $class => $source) {
            if ($this->settings->isEnabled($class)) {
                $this->warmUp($source);
                $cron_job_required = true;
            } else {
                $this->tearDown($source);
            }
        }

        if ($cron_job_required) {
            $this->cron_manager->activateJob(Jobs::EXTERNAL_BLOCKLIST_REFRESH);
        } else {
            $this->cron_manager->deactivateJob(Jobs::EXTERNAL_BLOCKLIST_REFRESH);
        }
    }


    /**
     * Warm up all enabled sources.
     */
    private function refreshSources(): void
    {
        foreach ($this->sources as $class => $source) {
            if ($this->settings->isEnabled($class)) {
                $this->warmUp($source);
            }
        }
    }


    /**
     * Read IP prefixes for $source from local cache.
     */
    private function populate(Source $source): void
    {
        $source->setIpPrefixes(Transients::getForSite(self::TRANSIENT_KEY, get_class($source)) ?: []);
    }


    /**
     * Update IP prefixes for $source and write them to local cache.
     */
    private function warmUp(Source $source): void
    {
        if ($source->updateIpPrefixes()) {
            // Cache IP ranges for one week only, don't rely on too old data.
            Transients::setForSite($source->getIpPrefixes(), WEEK_IN_SECONDS, self::TRANSIENT_KEY, get_class($source));
        } else {
            if (Is::cli()) {
                // In PHP CLI context (unit and integration tests), throw an exception.
                throw new WarmUpException(\sprintf('Failed to warm up "%s" blocklist.', $source->getTitle()));
            }
        }
    }


    /**
     * Remove IP prefixes for $source from local cache.
     */
    private function tearDown(Source $source): void
    {
        Transients::deleteFromSite(self::TRANSIENT_KEY, get_class($source));
    }
}
