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
        $this->page_title = _x('Scanner Blocker', 'Dashboard page title', 'bc-security');
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
        echo '<p>' . esc_html__('Scanner Blocker enables you to automatically block remote IP addresses that are scanning your website for weaknesses. A weakness can be known vulnerable plugin file, forgotten backup file or PHP script used for administrative purposes.', 'bc-security') . '</p>';
        echo '<p>' . sprintf(esc_html__('%s: Scanner Blocker does not prevent attackers from accessing such files if they really exist on your webspace! As the blocking happens on application level, only requests that are served by WordPress can be blocked.', 'bc-security'), '<strong>' . esc_html__('Important', 'bc-security') . '</strong>') . '</p>';
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

        // Section: Ban settings
        $this->addSettingsSection(
            'scanner-blocker-settings',
            _x('Settings', 'Settings section title', 'bc-security')
        );

        $this->addSettingsField(
            Settings::BAN_DURATION,
            __('Ban duration', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('minutes', 'bc-security'), ]
        );

        // Section: Built-in rules
        $this->addSettingsSection(
            'builtin-rules',
            _x('Built-in rules', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('Built-in rules cover most common scanning scenarios.', 'bc-security') . '</p>';
            }
        );

        foreach (BuiltInRules::enlist() as $identifier => $rule) {
            $this->addSettingsField(
                $identifier,
                $rule->getName(),
                [FormHelper::class, 'printCheckbox'],
                [
                    'description' => $rule->getDescription(),
                ]
            );
        }

        // Section: Custom rules
        $this->addSettingsSection(
            'custom-scanner-blocker-rules',
            _x('Custom rules', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . \sprintf(
                    /* translators: 1: link to regex101.com */
                    esc_html__('Using the field below you may add your own rules in form of regular expression. %1$s before use!', 'bc-security'),
                    '<a href="https://regex101.com/" rel="noreferrer">' . esc_html__('Test the expressions', 'bc-security') . '</a>'
                ) . '</p>';
            }
        );

        $this->addSettingsField(
            Settings::BAD_REQUEST_PATTERNS,
            __('Bad request patterns', 'bc-security'),
            [FormHelper::class, 'printTextArea'],
            [
                'append' => sprintf(
                    __('Enter one pattern per line. Any line starting with %s will be treated as comment. All patterns are automatically handled in case-insensitive manner.', 'bc-security'),
                    Settings::BAD_REQUEST_PATTERN_COMMENT_PREFIX
                ),
                'cols' => 60,
            ]
        );
    }
}
