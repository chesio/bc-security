<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Integration\Cases\Helpers;

use BlueChip\Security\Helpers\Hooks;
use BlueChip\Security\Helpers\Is;

class IsTest extends \BlueChip\Security\Tests\Integration\TestCase
{
    /**
     * Test Is::admin() method and `bc-security/filter:is-admin` filter.
     */
    public function testIsAdmin()
    {
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        $author_id = $this->factory->user->create(['role' => 'author']);

        $admin = get_user_by('ID', $admin_id);
        $author = get_user_by('ID', $author_id);

        // No filter set.
        $this->assertTrue(Is::admin($admin));
        $this->assertFalse(Is::admin($author));

        // Everyone is admin...
        add_filter(Hooks::IS_ADMIN, '__return_true');
        // ... even author:
        $this->assertTrue(Is::admin($author));

        // No one is admin...
        add_filter(Hooks::IS_ADMIN, '__return_false');
        //... not even admin.
        $this->assertFalse(Is::admin($admin));
    }
}
