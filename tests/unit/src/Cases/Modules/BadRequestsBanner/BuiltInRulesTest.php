<?php

namespace BlueChip\Security\Tests\Unit\Cases\Modules\BadRequestsBanner;

use BlueChip\Security\Modules\BadRequestsBanner\BuiltInRules;

class BuiltInRulesTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    public function provideUrisForBackupFilesRule(): array
    {
        return [
            'PHP file' => ['wp-config.php', false],
            'Image file' => ['dummy.png', false],
            'Backup file' => ['website-backup.zip', true],
            'Backup (back) file' => ['wp-config.php.back', true],
            'Backup (old) file' => ['script.php.old', true],
            'Backup (tmp) file' => ['some/important/file.tmp', true],
        ];
    }

    public function provideUrisForPhpFilesRule(): array
    {
        return [
            'PHP file' => ['wp-config.php', true],
            'Image file' => ['dummy.png', false],
            'Backup file' => ['website-backup.zip', false],
            'Backup (back) file' => ['wp-config.php.back', false],
            'Backup (old) file' => ['script.php.old', false],
            'Backup (tmp) file' => ['some/important/file.tmp', false],
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
}
