<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\BadRequestsBanner;

abstract class BuiltInRules
{
    public const BACKUP_FILES = 'backup-files';

    private const BACKUP_FILES_PATTERN = 'backup|(\.(back|old|tmp)$)';

    public const PHP_FILES = 'php-files';

    private const PHP_FILES_PATTERN = '\.php$';

    /**
     * @return array<string,BanRule>
     */
    public static function enlist(): array
    {
        return [
            self::PHP_FILES => new BanRule(
                __('Non-existent PHP files', 'bc-security'),
                self::PHP_FILES_PATTERN,
                __('(any URI targeting file with .php extension)', 'bc-security')
            ),
            self::BACKUP_FILES => new BanRule(
                __('Non-existent backup files', 'bc-security'),
                self::BACKUP_FILES_PATTERN,
                __('(any URI targeting file with backup in basename or with .back, .old or .tmp extension)', 'bc-security')
            ),
        ];
    }

    public static function get(string $identifier): ?BanRule
    {
        return self::enlist()[$identifier] ?? null;
    }
}
