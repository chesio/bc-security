<?php

namespace BlueChip\Security\Modules\BadRequestsBanner;

use BlueChip\Security\Core\Admin\AbstractPage;
use BlueChip\Security\Core\Admin\SettingsPage;
use BlueChip\Security\Helpers\FormHelper;
use BlueChip\Security\Modules\InternalBlocklist\AdminPage as InternalBlocklistAdminPage;
use BlueChip\Security\Modules\InternalBlocklist\HtaccessSynchronizer;

class AdminPage extends AbstractPage
{
    use SettingsPage;

    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-bad-requests-banner';


    private HtaccessSynchronizer $htaccess_synchronizer;


    public function __construct(Settings $settings, HtaccessSynchronizer $htaccess_synchronizer)
    {
        $this->page_title = _x('Ban bad requests', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Bad Requests Banner', 'Dashboard menu item name', 'bc-security');

        $this->useSettings($settings);

        $this->htaccess_synchronizer = $htaccess_synchronizer;
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
        echo '<p>' . esc_html__('This module enables you to automatically block remote IP addresses that are scanning your website for weaknesses. A weakness can be known vulnerable plugin file, forgotten backup file or PHP script used for administrative purposes.', 'bc-security') . '</p>';
        echo '<p>' . esc_html__('Below you can activate some pre-configured rules or you can add your own rules. The rules are checked whenever a request to the website results in 404 error. If any rule matches the request URI, remote IP address is locked from accessing the website for configured amount of time.', 'bc-security') . '</p>';
        //
        if (!$this->htaccess_synchronizer->isAvailable()) {
            echo '<p>' . sprintf(
                /* translators: 1: bold indicator, 2: link to internal blocklist admin page */
                esc_html__('%1$s: It is strongly recommended that you enable %2$s of internal blocklist and .htaccess file in order to prevent locked bots from accessing existing files on your webserver!', 'bc-security'),
                '<strong>' . esc_html__('Important', 'bc-security') . '</strong>',
                '<a href="' . InternalBlocklistAdminPage::getPageUrl() . '#blocklist-synchronization">' . esc_html__('synchronization') . '</a>'
            ) . '</p>';
        }
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
            'bad-requests-banner-settings',
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
            'bad-requests-banner-builtin-rules',
            _x('Built-in rules', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('Built-in rules target most common indicators that request to non-existent file is in fact a scan attempt.', 'bc-security') . '</p>';
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
            'bad-requests-banner-custom-rules',
            _x('Custom rules', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . \sprintf(
                    /* translators: 1: link to regex101.com */
                    esc_html__('Using the field below you may add your own rules in form of regular expression. All patterns are automatically handled in case-insensitive manner. %1$s before use!', 'bc-security'),
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
                    __('Enter one pattern per line. Any line starting with %s will be treated as comment.', 'bc-security'),
                    Settings::BAD_REQUEST_PATTERN_COMMENT_PREFIX
                ),
                'cols' => 64,
                'rows' => 8,
            ]
        );
    }
}
