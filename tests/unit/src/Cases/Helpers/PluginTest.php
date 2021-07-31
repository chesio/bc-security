<?php

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use BlueChip\Security\Helpers\Plugin;

class PluginTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    public function provideWordPressOrgUriData(): array
    {
        return [
            // WordPress 5.7 and older does not support UpdateURI header.
            'empty' => [true, []],
            // WordPress.org domains:
            'wordpress.org' => [true, ['UpdateURI' => 'https://wordpress.org/plugins/example-plugin/']],
            'w.org' => [true, ['UpdateURI' => 'https://w.org/plugin/example-plugin']],
            // External domains:
            'example.com' => [false, ['UpdateURI' => 'https://www.example.com']],
            'github.com' => [false, ['UpdateURI' => 'https://www.github.com/chesio/bc-security']],
            // False value is supported as well:
            'false' => [false, ['UpdateURI' => 'False']],
        ];
    }


    /**
     * Check Plugin::hasWordPressOrgUpdateUri() method.
     *
     * @dataProvider provideWordPressOrgUriData
     */
    public function testHasWordPressOrgUpdateUri(bool $value, array $plugin_data)
    {
        $this->assertSame($value, Plugin::hasWordPressOrgUpdateUri($plugin_data));
    }
}
