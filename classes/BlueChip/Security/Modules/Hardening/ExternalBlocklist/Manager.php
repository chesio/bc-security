<?php

namespace BlueChip\Security\Modules\Hardening\ExternalBlocklist;

class Manager
{
    /**
     * Is $ip_address on any external blocklist with given $scope?
     *
     * @param string $ip_address IP address to check.
     * @param int $scope Blocklist scope.
     *
     * @return bool True if IP address is on blocklist with given scope, false otherwise.
     */
    public function isBlocked(string $ip_address, int $scope): bool
    {
        // TODO
        return false;
    }
}