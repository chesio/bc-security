<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Module;

interface Initializable
{
    /**
     * Initialize module (perform any tasks that should be done init hook)
     */
    public function init();
}
