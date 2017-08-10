<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Helpers\FormHelper;

/**
 * Admin page that displays log records.
 */
class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** Page has counter indicator */
    use \BlueChip\Security\Core\Admin\CountablePage;

    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;

    /** Page has list table */
    use \BlueChip\Security\Core\Admin\ListingPage;


    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-logs';


    /**
     * @var \BlueChip\Security\Modules\Log\Logger
     */
    private $logger;


    /**
     * @param \BlueChip\Security\Modules\Log\Settings $settings
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
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
     * Initialize settings page: add sections and fields.
     */
    public function init()
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current.
        $this->setSettingsPage(self::SLUG);

        // Section: Automatic clean-up configuration
        $this->addSettingsSection(
            'log-cleanup-configuration',
            _x('Automatic clean-up configuration', 'Settings section title', 'bc-security'),
            [$this, 'renderCleanupConfigurationHint']
        );
        $this->addSettingsField(
            Settings::LOG_MAX_AGE,
            __('Maximum age', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::LOG_MAX_SIZE,
            __('Maximum size', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('thousands', 'bc-security'), ]
        );
    }


    public function loadPage()
    {
        $this->resetCount();
        $this->displaySettingsErrors();
        $this->addPerPageOption();
        $this->initListTable();
    }


    /**
     * Render admin page.
     */
    public function render()
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
        echo $this->renderSettingsForm();

        echo '</div>';
    }


    public function renderCleanupConfigurationHint()
    {
        echo '<p>';
        echo esc_html__('Logs are cleaned automatically once a day based on the configuration below.', 'bc-security');
        echo '</p>';
    }


    /**
     * Initialize list table instance.
     */
    private function initListTable()
    {
        $this->list_table = new ListTable($this->getUrl(), $this->per_page_option_name, $this->logger);
        $this->list_table->prepare_items();
    }
}
