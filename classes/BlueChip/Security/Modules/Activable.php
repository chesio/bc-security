<?php

namespace BlueChip\Security\Modules;

interface Activable
{
    /**
     * Activate module (add cron jobs etc.)
     */
    public function activate();

    /**
     * Deactivate module (remove cron jobs etc.)
     */
    public function deactivate();
}
