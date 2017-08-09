<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Admin;

trait ListingPage
{
    /**
     * @var int Number of new records in list since the last time current user viewed the page
     */
    public $counter;

    /**
     * @var \BlueChip\Security\Core\ListTable
     */
    private $list_table;


    /**
     * Perform actions to be done on listing page construction.
     *
     * @param \BlueChip\Security\Modules\Accountable $accountant
     */
    private function constructAdminListingPage(\BlueChip\Security\Modules\Accountable $accountant)
    {
        $this->counter = $this->getNewRecordsCount($accountant, wp_get_current_user());

        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }


    private function loadAdminListingPage()
    {
        $this->addScreenOptions();
        $this->resetNewRecordsCount();
        $this->initListTable();
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


    abstract private function initListTable();


    /**
     * @param \WP_User $user
     * @return int Number of log records recorded since the last time user visited this page.
     */
    private function getNewRecordsCount(\BlueChip\Security\Modules\Accountable $accountant, \WP_User $user)
    {
        $last_visit_timestamp = get_user_meta($user->ID, self::LAST_VISIT_TIMESTAMP_META, true);

        return empty($last_visit_timestamp)
            ? $accountant->countAll()
            : $accountant->countFrom($last_visit_timestamp)
        ;
    }


    /**
     *
     */
    private function resetNewRecordsCount()
    {
        // Get current user.
        $user = wp_get_current_user();
        // Update current user's last view time for this page.
        update_user_meta($user->ID, self::LAST_VISIT_TIMESTAMP_META, current_time('timestamp'));
    }
}
