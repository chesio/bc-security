<?php

namespace BlueChip\Security\Modules;

interface Initializable
{
    /**
     * Initialize module (perform any tasks that should be done init hook)
     */
    public function init(): void;
}
