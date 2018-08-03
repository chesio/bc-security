<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Base class for basic checks.
 *
 * Basic checks do not depend on data from external resources and can be all run from within single execution thread.
 */
abstract class BasicCheck extends Check
{
}
