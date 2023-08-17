<?php

namespace BlueChip\Security\Modules\ScannerBlocker;

use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\InternalBlocklist\BanReason;
use BlueChip\Security\Modules\InternalBlocklist\Manager;
use WP;

/**
 * Listen to 404 events and ban remote address if the URI matches any of registered fail2ban patterns.
 */
class Core implements Initializable
{
    public function __construct(
        private string $remote_address,
        private string $server_address,
        private Settings $settings,
        private Manager $ib_manager,
    ) {
    }


    public function init(): void
    {
        // Run only if request did not originate from the webserver itself.
        if ($this->remote_address !== $this->server_address) {
            add_action('wp', [$this, 'check404Queries'], 100, 1); // Run late, allow others to interfere (do their stuff).
        }
    }


    /**
     * Catch 404 events (main queries that returned no results).
     *
     * Note: `parse_query` action cannot be used for 404 detection, because 404 state can be set as late as in WP::main().
     *
     * @see WP::main()
     */
    public function check404Queries(WP $wp): void
    {
        /** @var \WP_Query $wp_query */
        global $wp_query;

        if (!$wp_query->is_404()) {
            // Nothing to do here.
            return;
        }

        $request = $wp->request;

        if (($ban_rule = $this->isBadRequest($request)) && $this->banRemoteAddress($request)) {
            // If ban succeeded, trigger related event.
            do_action(Hooks::BAD_REQUEST_EVENT, $this->remote_address, $request, $ban_rule);
        }
    }


    /**
     * @return BanRule|null Ban rule that matched $uri or null if no such rule has been found.
     */
    private function isBadRequest(string $uri): ?BanRule
    {
        foreach ($this->settings->getActiveBanRules() as $ban_rule) {
            if ($ban_rule->matches($uri)) {
                return $ban_rule;
            }
        }

        return null;
    }


    /**
     * Ban remote address for access $request URI.
     */
    private function banRemoteAddress(string $request): bool
    {
        return $this->ib_manager->lock(
            $this->remote_address,
            $this->settings->getBanDuration(),
            Scope::WEBSITE,
            BanReason::BAD_REQUEST_BAN,
            sprintf(__('Banned for accessing: %s', 'bc-security'), $request),
        );
    }
}
