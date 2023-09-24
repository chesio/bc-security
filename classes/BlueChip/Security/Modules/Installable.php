<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules;

interface Installable
{
    /**
     * Install module (add DB tables etc.)
     */
    public function install(): void;

    /**
     * Uninstall module (drop DB tables etc.)
     */
    public function uninstall(): void;
}
