<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

/**
 * Admin page that displays log records.
 */
class AdminPage extends \BlueChip\Security\Core\AdminSettingsPage
{
    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-logs';


    /**
     * @var \BlueChip\Security\Modules\Log\Logger
     */
    private $logger;

    /**
     * @var \BlueChip\Security\Modules\Log\ListTable
     */
    private $list_table;


    /**
     * @param \BlueChip\Security\Modules\Log\Settings $settings
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
    function __construct(Settings $settings, Logger $logger)
    {
        parent::__construct($settings);

        $this->page_title = _x('Log records', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Logs', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;

        $this->logger = $logger;

        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }


    /**
     * Run on `admin_init` hook.
     */
    public function admin_init()
    {
        // Form helper is going to be useful here.
        $form_helper = new \BlueChip\Security\Helpers\FormHelper();

        // Shortcut
        $settings_api_helper = $this->settings_api_helper;

        // Register setting first.
        $settings_api_helper->register();

        // Set page as current.
        $settings_api_helper->setSettingsPage($this->slug);

        // Section: Automatic clean-up configuration
        $settings_api_helper->addSettingsSection(
            'log-cleanup-configuration',
            _x('Automatic clean-up configuration', 'Settings section title', 'bc-security'),
            [$this, 'renderCleanupConfigurationHint']
        );
        $settings_api_helper->addSettingsField(
            Settings::LOG_MAX_AGE,
            __('Maximum age', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );
        $settings_api_helper->addSettingsField(
            Settings::LOG_MAX_SIZE,
            __('Maximum size', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('thousands', 'bc-security'), ]
        );
    }


    public function loadPage()
    {
        // To have admin notices displayed.
        parent::loadPage();

        $this->addScreenOptions();
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
        echo $this->settings_api_helper->renderForm();

        echo '</div>';
    }


    public function renderCleanupConfigurationHint()
    {
        echo '<p>';
        echo esc_html__('Logs are cleaned automatically once a day based on the configuration below.', 'bc-security');
        echo '</p>';
    }


    /**
     * @param bool $status
     * @param string $option
     * @param string $value
     * @return mixed
     */
    public function setScreenOption($status, $option, $value)
    {
        return ($option === ListTable::RECORDS_PER_PAGE) ? intval($value) : $status;
    }


    private function addScreenOptions()
    {
        add_screen_option('per_page', [
            'label' => __('Records', 'bc-security'),
            'default' => 20,
            'option' => ListTable::RECORDS_PER_PAGE,
        ]);
    }


    /**
     * Initialize list table instance.
     */
    private function initListTable()
    {
        $this->list_table = new ListTable($this->getUrl(), $this->logger);
        $this->list_table->prepare_items();
    }
}
