<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

/**
 * Admin page that displays log records.
 */
class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-logs';


    /** @var \BlueChip\Security\Modules\Log\Logger */
    private $logger;

    /** @var \BlueChip\Security\Modules\Log\ListTable */
    private $list_table;


    /**
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
    function __construct(Logger $logger)
    {
        $this->page_title = _x('Log records', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Logs', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;

        $this->logger = $logger;

        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }


    public function loadPage()
    {
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
        // Table
        $this->list_table->views();
        echo '<form method="post">';
        $this->list_table->display();
        echo '</form>';
        echo '</div>';
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
     * Initialize list table instance
     */
    private function initListTable()
    {
        $this->list_table = new ListTable($this->getUrl(), $this->logger);
        $this->list_table->prepare_items();
    }
}
