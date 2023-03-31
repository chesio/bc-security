<?php

namespace BlueChip\Security\Modules\Services\ReverseDnsLookup;

use BlueChip\Security\Helpers\Transients;
use BlueChip\Security\Modules;

/**
 * Helper class that performs hostname resolution.
 */
class Resolver implements Modules\Activable, Modules\Initializable
{
    /**
     * @var string Name of cron job action used for non-blocking remote hostname resolution.
     */
    private const RESOLVE_REMOTE_ADDRESS = 'bc-security/resolve-remote-address';

    /**
     * @var string Cache key under which to remote hostnames are cached.
     */
    private const TRANSIENT_KEY = 'remote-hostname';

    /**
     * @var int Number of seconds to cache remote hostname resolution results.
     */
    private const CACHE_TTL = DAY_IN_SECONDS;


    public function activate(): void
    {
        // Do nothing.
    }


    public function deactivate(): void
    {
        // Unschedule all cron jobs consumed by this module.
        wp_unschedule_hook(self::RESOLVE_REMOTE_ADDRESS);
    }


    public function init(): void
    {
        // Register action for non-blocking hostname resolution.
        add_action(self::RESOLVE_REMOTE_ADDRESS, [$this, 'resolveHostname'], 10, 3);
    }


    /**
     * Resolve remote hostname of given IP address and run given action.
     *
     * The action receives IP address as first argument, hostname as second and then all values from context.
     *
     * @param string $ip_address Remote IP address to resolve.
     * @param string $action Name of action to invoke with resolved hostname.
     * @param array<string,mixed> $context Additional parameters that are passed to the action.
     */
    public function resolveHostname(string $ip_address, string $action, array $context): void
    {
        if (!empty($hostname = $this->resolveHostnameInForeground($ip_address))) {
            do_action($action, new Response($ip_address, $hostname, $context));
        }
    }


    /**
     * Get hostname of given IP address in non-blocking way. When the hostname is resolved, given action is run.
     *
     * @internal Schedules remote hostname resolution to run via WP-Cron.
     *
     * @param string $ip_address Remote IP address to resolve.
     * @param string $action Name of action to call when remote hostname is resolved.
     * @param array<string,mixed> $context [optional] Additional parameters to pass to the action.
     */
    public function resolveHostnameInBackground(string $ip_address, string $action, array $context = []): void
    {
        wp_schedule_single_event(\time(), self::RESOLVE_REMOTE_ADDRESS, [$ip_address, $action, $context]);
    }


    /**
     * Get hostname for remote IP address in blocking way.
     *
     * @param string $ip_address Remote IP address to resolve.
     *
     * @return string Remote hostname on success, IP address if hostname could not be resolved, empty string on failure.
     */
    public function resolveHostnameInForeground(string $ip_address): string
    {
        // Check the cache first.
        if (empty($hostname = Transients::getForSite(self::TRANSIENT_KEY, $ip_address))) {
            // Cache empty, resolve the hostname.
            if (empty($hostname = \gethostbyaddr($ip_address) ?: '')) {
                // This should only happen on malformed IP address, but bail nevertheless.
                return '';
            }

            // Cache the hostname for one day.
            Transients::setForSite($hostname, self::CACHE_TTL, self::TRANSIENT_KEY, $ip_address);
        }

        // Return the hostname.
        return $hostname;
    }
}
