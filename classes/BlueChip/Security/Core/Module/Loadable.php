<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Module;

interface Loadable
{
    /**
     * Load module (perform any tasks that should be done immediately on plugin load)
     */
    public function load();
}
