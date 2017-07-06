<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Module;

interface Initializable
{
    /**
     * Initialize module (set hooks etc.)
     */
    public function init();
}
