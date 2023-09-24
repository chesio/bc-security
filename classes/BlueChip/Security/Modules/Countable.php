<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules;

interface Countable
{
    /**
     * Return number of all items.
     *
     * @return int Number of all items.
     */
    public function countAll(): int;

    /**
     * Return number of all items newer than given $timestamp.
     *
     * @param int $timestamp
     *
     * @return int Number of all items newer than given $timestamp.
     */
    public function countFrom(int $timestamp): int;
}
