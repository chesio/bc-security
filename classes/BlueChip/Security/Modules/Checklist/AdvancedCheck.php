<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Base class for advanced checks. Advanced checks depend on data from external resources.
 */
abstract class AdvancedCheck extends Check
{
    /**
     * @var string
     */
    const CHECK_CLASS = 'advanced';
}
