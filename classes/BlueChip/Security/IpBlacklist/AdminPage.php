<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\IpBlacklist;

use BlueChip\Security\Core\Helpers\AdminNotices;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-ip-blacklist';

    /** @var string Name of nonce used for any action on this page */
    const NONCE_NAME = '_wpnonce';

    /** @var Name for prune action wp_nonce action */
    const PRUNE_NONCE_ACTION = 'prune-ip-blacklist';

    /** @var Name for prune action submit button */
    const PRUNE_SUBMIT = 'prune-ip-blacklist-submit';


    /** @var \BlueChip\Security\IpBlacklist\Manager */
    private $bl_manager;

    /** @var \BlueChip\Security\IpBlacklist\ListTable */
    private $list_table;


    /**
     * @param \BlueChip\Security\IpBlacklist\Manager $bl_manager
     */
    function __construct(Manager $bl_manager)
    {
        $this->page_title = _x('IP Blacklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('IP Blacklist', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;

        $this->bl_manager = $bl_manager;

        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }


    public function loadPage()
    {
        $this->processActions();
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
        // Actions
        echo '<form method="post">';
        wp_nonce_field(self::PRUNE_NONCE_ACTION);
        submit_button(__('Prune IP blacklist', 'bc-security'), 'delete', self::PRUNE_SUBMIT);
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
        $this->list_table = new ListTable($this->bl_manager, $this->getUrl());
        $this->list_table->processActions(); // may trigger wp_redirect()
        $this->list_table->displayNotices();
        $this->list_table->prepare_items();
    }


    private function processActions()
    {
        $nonce = filter_input(INPUT_POST, self::NONCE_NAME, FILTER_SANITIZE_STRING);
        if (empty($nonce)) {
            // No nonce, no action.
            return;
        }

        if (isset($_POST[self::PRUNE_SUBMIT]) && wp_verify_nonce($nonce, self::PRUNE_NONCE_ACTION)) {
            // Prune blacklist.
            // TODO: wp_redirect after pruning?
            if ($this->bl_manager->prune()) {
                AdminNotices::add(
                    __('Expired entries have been removed from IP blacklist.', 'bc-security'), AdminNotices::SUCCESS
                );
            } else {
                AdminNotices::add(
                   __('Failed to remove expired entries from IP blacklist.', 'bc-security'), AdminNotices::ERROR
                );
            }
        }
    }
}
