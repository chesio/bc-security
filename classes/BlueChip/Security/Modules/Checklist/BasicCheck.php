<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Base class for basic checks. Basic checks do not depend on data from external resources.
 */
abstract class BasicCheck extends Check
{
    /**
     * @var string
     */
    const CHECK_CLASS = 'basic';
}
