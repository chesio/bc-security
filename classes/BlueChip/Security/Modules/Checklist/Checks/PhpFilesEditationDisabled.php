<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class PhpFilesEditationDisabled extends Checklist\Check
{
    public function __construct()
    {
        parent::__construct(
            __('PHP files editation disabled', 'bc-security'),
            sprintf(__('It is generally recommended to <a href="%s">disable editation of PHP files</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Disable_File_Editing')
        );
    }


    public function run(): Checklist\CheckResult
    {
        return defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT
            ? new Checklist\CheckResult(true, esc_html__('Theme and plugin files cannot be edited from backend.', 'bc-security'))
            : new Checklist\CheckResult(false, esc_html__('Theme and plugin files can be edited from backend!', 'bc-security'))
        ;
    }
}
