<?php
/**
 * @package BC_Security
 */
namespace BlueChip\Security\Modules;

/**
 * @internal Surely the ugliest English "word" that I came up with in a long time...
 */
interface Deactivateable
{
    /**
     * Deactivate module (clean caches, URL redirects etc.)
     */
    public function deactivate();
}
