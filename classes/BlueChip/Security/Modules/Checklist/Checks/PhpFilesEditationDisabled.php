<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class PhpFilesEditationDisabled extends Checklist\BasicCheck
{
    public function __construct()
    {
        parent::__construct(
            __('PHP files editation disabled', 'bc-security'),
            sprintf(
                /* translators: 1: link to Codex page on WordPress hardening */
                esc_html__('It is generally recommended to %1$s.', 'bc-security'),
                '<a href="' . esc_url(__('https://codex.wordpress.org/Hardening_WordPress#Disable_File_Editing', 'bc-security')) . '" rel="noreferrer">' . esc_html__('disable editation of PHP files', 'bc-security') . '</a>'
            )
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        return defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT
            ? new Checklist\CheckResult(true, esc_html__('Theme and plugin files cannot be edited from backend.', 'bc-security'))
            : new Checklist\CheckResult(false, esc_html__('Theme and plugin files can be edited from backend!', 'bc-security'))
        ;
    }
}
