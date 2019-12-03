<?php

namespace BlueChip\Security\Modules;

interface Installable
{
    /**
     * Install module (add DB tables etc.)
     */
    public function install();

    /**
     * Uninstall module (drop DB tables etc.)
     */
    public function uninstall();
}
