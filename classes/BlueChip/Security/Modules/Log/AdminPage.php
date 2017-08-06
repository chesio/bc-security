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
    use \BlueChip\Security\Core\Admin\SettingsPage;

    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-logs';

    /**
     * @var string Name of user meta key for last view time
     */
    const LAST_VISIT_TIMESTAMP_META = 'bc-security/logs-last-visit';


    /**
     * @var \BlueChip\Security\Modules\Log\Logger
     */
    private $logger;

    /**
     * @var \BlueChip\Security\Modules\Log\ListTable
     */
    private $list_table;

    /**
     * @var int Number of new records in log since the last time current user viewed the page
     */
    public $counter;


    /**
     * @param \BlueChip\Security\Modules\Log\Settings $settings
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
    public function __construct(Settings $settings, Logger $logger)
    {
        $this->page_title = _x('Log records', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Logs', 'Dashboard menu item name', 'bc-security');

        $this->logger = $logger;
        $this->counter = $this->getNewRecordsCount(wp_get_current_user());

        $this->constructSettingsPage($settings);

        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }


    /**
     * Initialize settings page: add sections and fields.
     */
    public function initSettingsPageSectionsAndFields()
    {
        // Shortcut
        $settings_api_helper = $this->settings_api_helper;

        // Set page as current.
        $settings_api_helper->setSettingsPage(self::SLUG);

        // Section: Automatic clean-up configuration
        $settings_api_helper->addSettingsSection(
            'log-cleanup-configuration',
            _x('Automatic clean-up configuration', 'Settings section title', 'bc-security'),
            [$this, 'renderCleanupConfigurationHint']
        );
        $settings_api_helper->addSettingsField(
            Settings::LOG_MAX_AGE,
            __('Maximum age', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );
        $settings_api_helper->addSettingsField(
            Settings::LOG_MAX_SIZE,
            __('Maximum size', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('thousands', 'bc-security'), ]
        );
    }


    public function loadPage()
    {
        // To have admin notices displayed.
        $this->loadSettingsPage();
        $this->addScreenOptions();
        $this->resetNewRecordsCount(wp_get_current_user());
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
        echo $this->renderForm();

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
     * @param \WP_User $user
     * @return int Number of log records recorded since the last time user visited this page.
     */
    private function getNewRecordsCount(\WP_User $user)
    {
        $last_visit_timestamp = get_user_meta($user->ID, self::LAST_VISIT_TIMESTAMP_META, true);

        return empty($last_visit_timestamp)
            ? $this->logger->countAll()
            : $this->logger->countFrom($last_visit_timestamp)
        ;
    }


    /**
     * @param \WP_User $user
     */
    private function resetNewRecordsCount(\WP_User $user)
    {
        // Update $user's last view time for this page.
        update_user_meta($user->ID, self::LAST_VISIT_TIMESTAMP_META, current_time('timestamp'));
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
