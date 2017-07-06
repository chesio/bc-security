<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Module;

interface Installable
{
    /**
     * Install module (add DB tables etc.)
     */
    public function install();
}
