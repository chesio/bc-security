<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\Cron\Jobs;
use BlueChip\Security\Modules\Cron\Manager as CronManager;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;
use BlueChip\Security\Modules\Initializable;
use InvalidArgumentException;

class Manager implements Initializable
{
    /**
     * @var array<string, array<string, string>> List of available sources for external blocklists.
     */
    private const SOURCES = [
        AmazonWebServices::class => [
            'settings_key' => Settings::AMAZON_WEB_SERVICES,
            'cron_job_id' => Jobs::AWS_IP_PREFIXES_REFRESH,
        ],
    ];

    /**
     * @var array<int, Blocklist> List of external blocklists per access scope.
     */
    private $blocklists = [];

    /**
     * @var CronManager
     */
    private $cron_manager;

    /**
     * @var Settings
     */
    private $settings;


    public function __construct(Settings $settings, CronManager $cron_manager)
    {
        $this->cron_manager = $cron_manager;
        $this->settings = $settings;

        foreach (Scope::enlist() as $access_scope) {
            if ($access_scope !== Scope::ANY) {
                $this->blocklists[$access_scope] = null;
            }
        }
    }


    public function init(): void
    {
        // Some cron jobs needs to be (de)activated depending on active hardening options settings.
        $this->settings->addUpdateHook([$this, 'updateBlocklists']);

        // Set warm up cron jobs for all enabled sources.
        foreach (self::SOURCES as $class => ['settings_key' => $key, 'cron_job_id' => $cron_job_id]) {
            $access_scope = $this->settings[$key];
            if ($access_scope !== Scope::ANY) {
                add_action($cron_job_id, [new $class(), 'warmUp'], 10, 0);
            }
        }
    }


    public function getBlocklist(int $access_scope): Blocklist
    {
        // Blocklist for non-scope should not be requested.
        if ($access_scope === Scope::ANY) {
            throw new InvalidArgumentException("Cannot get blocklist for unspecified access scope!");
        }

        // Lazy load the blocklist for given access scope if necessary.
        if ($this->blocklists[$access_scope] === null) {
            $blocklist = new Blocklist();

            foreach (self::SOURCES as $class => ['settings_key' => $key]) {
                if ($this->settings[$key] === $access_scope) {
                    $blocklist->addIpPrefixes(new $class());
                }
            }

            $this->blocklists[$access_scope] = $blocklist;
        }

        return $this->blocklists[$access_scope];
    }


    /**
     * Is $ip_address on any external blocklist with given $access_scope?
     *
     * @param string $ip_address IP address to check.
     * @param int $access_scope Access scope to check.
     *
     * @return bool True if IP address is on blocklist with given access scope, false otherwise.
     */
    public function isBlocked(string $ip_address, int $access_scope): bool
    {
        return $this->getBlocklist($access_scope)->hasIpAddress($ip_address);
    }


    /**
     * Warm up or tear down blocklist sourcees depending on their activation status.
     * Also activate or deactivate cron jobs to rerun warm up in the background.
     */
    public function updateBlocklists(): void
    {
        foreach (self::SOURCES as $class => ['settings_key' => $key, 'cron_job_id' => $cron_job_id]) {
            $access_scope = $this->settings[$key];
            // Source needs to be regularly updated only if there is access lock scope set.
            if ($access_scope !== Scope::ANY) {
                (new $class())->warmUp();
                $this->cron_manager->activateJob($cron_job_id);
            } else {
                (new $class())->tearDown();
                $this->cron_manager->deactivateJob($cron_job_id);
            }
        }
    }
}
