<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules;

interface Activable
{
    /**
     * Activate module (add cron jobs etc.)
     */
    public function activate(): void;

    /**
     * Deactivate module (remove cron jobs etc.)
     */
    public function deactivate(): void;
}
