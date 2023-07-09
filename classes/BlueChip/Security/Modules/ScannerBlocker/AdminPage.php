<?php

namespace BlueChip\Security\Modules\ScannerBlocker;

use BlueChip\Security\Core\Admin\AbstractPage;
use BlueChip\Security\Core\Admin\SettingsPage;
use BlueChip\Security\Helpers\FormHelper;

class AdminPage extends AbstractPage
{
    use SettingsPage;

    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-scanner-blocker';


    public function __construct(Settings $settings)
    {
        $this->page_title = _x('Block scanners', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Scanner Blocker', 'Dashboard menu item name', 'bc-security');

        $this->useSettings($settings);
    }


    public function loadPage(): void
    {
        $this->displaySettingsErrors();
    }


    /**
     * Output page contents.
     */
    public function printContents(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>' . esc_html__('Work in progress', 'bc-security') . '</p>';
        // Settings form
        $this->printSettingsForm();
        echo '</div>';
    }

    /**
     * Initialize settings page: add sections and fields.
     */
    public function initPage(): void
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current.
        $this->setSettingsPage(self::SLUG);

        // Section: Block scanners
        $this->addSettingsSection(
            'scanner-blocker',
            _x('Block scanners', 'Settings section title', 'bc-security'),
            function () {
                // TODO
            }
        );

        $this->addSettingsField(
            Settings::BAD_REQUEST_PATTERNS,
            __('Bad request patterns', 'bc-security'),
            [FormHelper::class, 'printTextArea'],
            [
                'append' => sprintf(
                    __('Enter one pattern per line. Any line starting with %s will be treated as comment.', 'bc-security'),
                    Settings::BAD_REQUEST_PATTERN_COMMENT_PREFIX
                ),
            ]
        );

        $this->addSettingsField(
            Settings::BAN_DURATION,
            __('Ban duration', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('minutes', 'bc-security'), ]
        );
    }
}
