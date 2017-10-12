<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules;

interface Countable
{
    /**
     * Return number of all items.
     */
    public function countAll();

    /**
     * Return number of all items newer than given $timestamp.
     *
     * @param int $timestamp
     */
    public function countFrom($timestamp);
}
