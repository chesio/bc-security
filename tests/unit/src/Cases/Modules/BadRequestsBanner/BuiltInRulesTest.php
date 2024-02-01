<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Modules\BadRequestsBanner;

use BlueChip\Security\Modules\BadRequestsBanner\BuiltInRules;

class BuiltInRulesTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    public function provideUrisForBackupFilesRule(): array
    {
        return [
            'Backup file' => ['website-backup.zip', true],
            'Backup (back) file' => ['wp-config.php.back', true],
            'Backup (old) file' => ['script.php.old', true],
            'Backup (tmp) file' => ['some/important/file.tmp', true],
            'Image file' => ['dummy.png', false],
            'PHP file' => ['wp-config.php', false],
            'Readme.txt file' => ['wp-content/some-plugin/readme.txt', false],
        ];
    }

    public function provideUrisForPhpFilesRule(): array
    {
        return [
            'Backup file' => ['website-backup.zip', false],
            'Backup (back) file' => ['wp-config.php.back', false],
            'Backup (old) file' => ['script.php.old', false],
            'Backup (tmp) file' => ['some/important/file.tmp', false],
            'Image file' => ['dummy.png', false],
            'PHP file' => ['wp-config.php', true],
            'Readme.txt file' => ['wp-content/some-plugin/readme.txt', false],
        ];
    }

    public function provideUrisForReadmeTxtFilesRule(): array
    {
        return [
            'Backup file' => ['website-backup.zip', false],
            'Backup (back) file' => ['wp-config.php.back', false],
            'Backup (old) file' => ['script.php.old', false],
            'Backup (tmp) file' => ['some/important/file.tmp', false],
            'Image file' => ['dummy.png', false],
            'PHP file' => ['wp-config.php', false],
            'Readme.txt file' => ['wp-content/some-plugin/readme.txt', true],
        ];
    }

    /**
     * @dataProvider provideUrisForBackupFilesRule
     */
    public function testBackupFilesRule(string $uri, bool $result): void
    {
        $ban_rule = BuiltInRules::get(BuiltInRules::BACKUP_FILES);

        $this->assertSame($ban_rule->matches($uri), $result);
    }

    /**
     * @dataProvider provideUrisForPhpFilesRule
     */
    public function testPhpFilesRule(string $uri, bool $result): void
    {
        $ban_rule = BuiltInRules::get(BuiltInRules::PHP_FILES);

        $this->assertSame($ban_rule->matches($uri), $result);
    }

    /**
     * @dataProvider provideUrisForReadmeTxtFilesRule
     */
    public function testReadmeTxtFilesRule(string $uri, bool $result): void
    {
        $ban_rule = BuiltInRules::get(BuiltInRules::README_FILES);

        $this->assertSame($ban_rule->matches($uri), $result);
    }
}
