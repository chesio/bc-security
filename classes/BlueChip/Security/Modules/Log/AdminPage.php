<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Core\Admin\AbstractPage;
use BlueChip\Security\Core\Admin\CountablePage;
use BlueChip\Security\Core\Admin\ListingPage;
use BlueChip\Security\Core\Admin\SettingsPage;
use BlueChip\Security\Helpers\FormHelper;

/**
 * Admin page that displays log records.
 */
class AdminPage extends AbstractPage
{
    /** Page has counter indicator */
    use CountablePage;

    /** Page has settings section */
    use SettingsPage;

    /** Page has list table */
    use ListingPage;


    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-logs';


    private Logger $logger;


    public function __construct(Settings $settings, Logger $logger)
    {
        $this->page_title = _x('Log records', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Logs', 'Dashboard menu item name', 'bc-security');

        $this->logger = $logger;

        $this->setCounter($logger);
        $this->useSettings($settings);
        $this->setPerPageOption('bc_security_log_records_per_page');
    }


    /**
     * @param string|null $event_id [optional] If provided, URL to list table view for given event is returned.
     *
     * @return string URL of admin page.
     */
    public static function getPageUrl(?string $event_id = null): string
    {
        return ($event_id === null) ? parent::getPageUrl() : ListTable::getViewUrl(parent::getPageUrl(), $event_id);
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

        // Section: Automatic clean-up configuration
        $this->addSettingsSection(
            'log-cleanup-configuration',
            _x('Automatic clean-up', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>';
                echo esc_html__('Logs are cleaned automatically once a day based on the configuration below.', 'bc-security');
                echo '</p>';
            }
        );
        $this->addSettingsField(
            Settings::LOG_MAX_AGE,
            __('Maximum age', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::LOG_MAX_SIZE,
            __('Maximum size', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('thousands', 'bc-security'), ]
        );
    }


    protected function loadPage(): void
    {
        $this->resetCount();
        $this->displaySettingsErrors();
        $this->addPerPageOption();
        $this->initListTable();
    }


    /**
     * Output page contents.
     */
    public function printContents(): void
    {
        echo '<div class="wrap">';

        // Page heading
        echo '<h1>' . esc_html($this->page_title) . '</h1>';

        // Logs table
        $this->list_table->views();
        echo '<form method="post">';
        $this->list_table->display();
        echo '</form>';

        // Pruning configuration form
        $this->printSettingsForm();

        echo '</div>';
    }


    /**
     * Initialize list table instance.
     */
    private function initListTable(): void
    {
        $this->list_table = new ListTable($this->getUrl(), $this->per_page_option_name, $this->logger);
        $this->list_table->prepare_items();
    }
}
