<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules;

interface Loadable
{
    /**
     * Load module (perform any tasks that should be done immediately on plugin load)
     */
    public function load(): void;
}
