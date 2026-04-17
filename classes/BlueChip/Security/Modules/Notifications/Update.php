<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Notifications;

class Update
{
    public function __construct(public readonly string $new_version = '')
    {
    }

    /**
     * @param array<string,mixed> $data Response data to process/sanitize.
     *
     * @return self|null Update instance based on $data or null in case $data is corrupted.
     */
    public static function createFromArray(array $data): ?self
    {
        $new_version = $data['new_version'] ?? null;
        return (\is_string($new_version) && ($new_version !== '')) ? (new self($new_version)) : null;
    }

    /**
     * @param object $data Response data to process/sanitize.
     *
     * @return self|null Update instance based on $data or null in case $data is corrupted.
     */
    public static function createFromObject(object $data): ?self
    {
        $new_version = $data->new_version ?? null;
        return (\is_string($new_version) && ($new_version !== '')) ? (new self($new_version)) : null;
    }
}
