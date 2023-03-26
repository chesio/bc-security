<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

interface Source
{
    /**
     * @return string[] List of IP prefixes for this source.
     */
    public function getIpPrefixes(): array;

    /**
     * @return int Count of (locally cached) IP prefixes for this source.
     */
    public function getSize(): int;

    /**
     * Update locally cached IP prefixes for this source.
     */
    public function warmUp(): void;

    /**
     * Remove any locally cached IP prefixes for this source.
     */
    public function tearDown(): void;
}
