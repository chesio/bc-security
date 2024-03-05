<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\BadRequestsBanner;

abstract class BuiltInRules
{
    public const ARCHIVE_FILES = 'archive-files';

    private const ARCHIVE_FILES_PATTERN = '\.(tgz|zip)$';

    public const BACKUP_FILES = 'backup-files';

    private const BACKUP_FILES_PATTERN = 'backup|(\.(back|old|tmp)$)';

    public const PHP_FILES = 'php-files';

    private const PHP_FILES_PATTERN = '\.php$';

    public const README_FILES = 'readme-txt-files';

    private const README_FILES_PATTERN = '\/readme\.txt$';

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
            self::README_FILES => new BanRule(
                __('Non-existent readme.txt files', 'bc-security'),
                self::README_FILES_PATTERN,
                __('(any URI targeting /readme.txt file)', 'bc-security')
            ),
            self::ARCHIVE_FILES => new BanRule(
                __('Non-existent archive files', 'bc-security'),
                self::ARCHIVE_FILES_PATTERN,
                __('(any URI targeting file with .tgz or .zip extension)', 'bc-security')
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
