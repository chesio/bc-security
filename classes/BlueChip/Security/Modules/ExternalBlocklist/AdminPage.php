<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Core\Admin\AbstractPage;
use BlueChip\Security\Core\Admin\SettingsPage;
use BlueChip\Security\Helpers\FormHelper;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;

class AdminPage extends AbstractPage
{
    /** Page has settings section */
    use SettingsPage;


    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-external-blocklist';


    /**
     * @param Settings $settings Settings for external blocklist
     */
    public function __construct(Settings $settings)
    {
        $this->page_title = _x('External Blocklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('External Blocklist', 'Dashboard menu item name', 'bc-security');

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

        // Section: Block requests from Amazon Web Services.
        $this->addSettingsSection(
            'block-requests-from-aws',
            __('Block requests from Amazon Web Services', 'bc-security'),
            function () {
                echo '<p>' . \sprintf(
                    /* translators: 1: link to Wikipedia page about Amazon Web Services, 2: link to Wordfence Blog article on brute force attacks originating from AWS */
                    esc_html__('%1$s can be misused to perform automated attacks against WordPress websites - for example %2$s. Blocking requests from AWS might help reduce amount of such attacks.', 'bc-security'),
                    '<a href="https://en.wikipedia.org/wiki/Amazon_Web_Services" rel="noreferrer">' . esc_html__('Amazon Web Services', 'bc-security') . '</a>',
                    '<a href="https://www.wordfence.com/blog/2021/11/aws-attacks-targeting-wordpress-increase-5x/" rel="noreferrer">' . esc_html__('brute force logging attacks', 'bc-security') . '</a>'
                ) . '</p>';

                $ip_prefixes_count = (new AmazonWebServices())->getSize();
                if ($ip_prefixes_count > 0) {
                    echo '<p>' . \sprintf(
                        esc_html__('There are currently %s IP prefixes cached from this source.', 'bc-security'),
                        \sprintf('<strong>%d</strong>', $ip_prefixes_count)
                    );
                }
            }
        );
        $this->addSettingsField(
            Settings::AMAZON_WEB_SERVICES,
            __('Block requests from AWS', 'bc-security'),
            [FormHelper::class, 'printSelect'],
            ['options' => Scope::enlist(true)]
        );
    }
}
