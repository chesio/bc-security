<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use BlueChip\Security\Helpers\Plugin;

class PluginTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    public function provideWordPressOrgUriData(): array
    {
        return [
            // WordPress 5.7 and older does not support UpdateURI header.
            'missing' => [true, 'example-plugin/example-plugin.php', []],
            // No value - this seems to be the case for most of WordPress.org hosted plugins.
            'empty' => [true, 'example-plugin/example-plugin.php', ['UpdateURI' => '']],
            // WordPress.org domains:
            'wordpress.org' => [true, 'example-plugin/example-plugin.php', ['UpdateURI' => 'https://wordpress.org/plugins/example-plugin/']],
            'w.org' => [true, 'example-plugin/example-plugin.php', ['UpdateURI' => 'w.org/plugin/example-plugin']],
            // External domains:
            'example.com' => [false, 'example-plugin/example-plugin.php', ['UpdateURI' => 'https://www.example.com']],
            'github.com' => [false, 'bc-security/bc-security.php', ['UpdateURI' => 'https://www.github.com/chesio/bc-security']],
            // String value:
            'slug' => [false, 'my-custom-plugin-name/my-custom-plugin-name.php', ['UpdateURI' => 'my-custom-plugin-name']],
            // False value is supported as well:
            'false' => [false, 'example-plugin/example-plugin.php', ['UpdateURI' => 'false']],
        ];
    }


    /**
     * Check Plugin::hasWordPressOrgUpdateUri() method.
     *
     * @dataProvider provideWordPressOrgUriData
     */
    public function testHasWordPressOrgUpdateUri(bool $value, string $plugin_basename, array $plugin_data): void
    {
        $this->assertSame($value, Plugin::hasWordPressOrgUpdateUri($plugin_basename, $plugin_data));
    }
}
