<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class NoPluginsRemovedFromDirectory extends Checklist\Check
{
    public function __construct()
    {
        parent::__construct(
            __('No removed plugins installed', 'bc-security'),
            sprintf(__('Plugins can be removed from <a href="%s">Plugins Directory</a> for several reasons (including but no limited to <a href="%s">security vulnerability</a>). Use of removed plugins is discouraged.', 'bc-security'), 'https://wordpress.org/plugins/', 'https://www.wordfence.com/blog/2017/09/display-widgets-malware/')
        );
    }


    public function run(): Checklist\CheckResult
    {
        return new Checklist\CheckResult(null, '<em>Not yet implemented.</em>');
    }
}
