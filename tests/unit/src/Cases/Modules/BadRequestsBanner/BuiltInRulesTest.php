<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Modules\BadRequestsBanner;

use BlueChip\Security\Modules\BadRequestsBanner\BuiltInRules;
use BlueChip\Security\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class BuiltInRulesTest extends TestCase
{
    public static function provideUris(): array
    {
        return [
            'Archive (tgz) file' => [
                'data.tgz',
                [
                    BuiltInRules::ARCHIVE_FILES => true,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Archive and backup (zip) file' => [
                'website-backup.zip',
                [
                    BuiltInRules::ARCHIVE_FILES => true,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => true,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Backup (back) file' => [
                'wp-config.php.back',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => true,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Backup (old) file' => [
                'script.php.old',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => true,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Backup (tmp) file' => [
                'some/important/file.tmp',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => true,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'CSS asset' => [
                'wp-content/theme/dummy/styles.css',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Image file' => [
                'plugin/non-existent/image.png',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'JS asset' => [
                'wp-content/themes/dummy/script.js',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'ASP file' => [
                'backend.asp',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => true,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'ASPx file' => [
                'login.aspx',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => true,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'PHP file' => [
                '_wp-config.php',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => true,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Humans.txt file' => [
                'humans.txt',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => false,
                ],
            ],
            'Readme.txt file' => [
                'wp-content/plugins/some-plugin/readme.txt',
                [
                    BuiltInRules::ARCHIVE_FILES => false,
                    BuiltInRules::ASP_FILES => false,
                    BuiltInRules::BACKUP_FILES => false,
                    BuiltInRules::PHP_FILES => false,
                    BuiltInRules::README_FILES => true,
                ],
            ],
        ];
    }

    #[DataProvider('provideUris')]
    public function testBuiltInRules(string $uri, array $results): void
    {
        foreach ($results as $rule_identifier => $result) {
            $ban_rule = BuiltInRules::get($rule_identifier);
            $this->assertSame(
                $ban_rule->matches($uri),
                $result,
                sprintf($result ? 'Rule "%s" must not match URI %s' : 'Rule "%s" must match URI %s', $ban_rule->getName(), $uri)
            );
        }
    }
}
