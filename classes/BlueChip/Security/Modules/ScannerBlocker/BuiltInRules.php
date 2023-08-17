<?php

namespace BlueChip\Security\Modules\ScannerBlocker;

abstract class BuiltInRules
{
    public const BACKUP_FILES = 'backup-files';

    public const PHP_FILES = 'php-files';

    /**
     * @return array<string,BanRule>
     */
    public static function enlist(): array
    {
        return [
            self::PHP_FILES => new BanRule(
                __('Non-existent PHP files', 'bc-security'),
                '\.php$',
                __('(any URI targeting file with .php extension)', 'bc-security')
            ),
            self::BACKUP_FILES => new BanRule(
                __('Non-existent backup files', 'bc-security'),
                'backup|(\.(back|old|tmp)$)',
                __('(any URI targeting file with backup in basename or with .back, .old or .tmp extension)', 'bc-security')
            ),
        ];
    }

    public static function get(string $identifier): ?BanRule
    {
        return self::enlist()[$identifier] ?? null;
    }
}
