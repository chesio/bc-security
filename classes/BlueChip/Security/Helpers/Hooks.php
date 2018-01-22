<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Helpers;

/**
 * Hooks available within helper classes
 */
interface Hooks
{
    /**
     * Filter: allows to change return value of Is::admin() helper.
     *
     * @see \BlueChip\Security\Helpers\Is::admin()
     */
    const IS_ADMIN = 'bc-security/filter:is-admin';
}
