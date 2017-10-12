<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Admin;

/**
 * Provide information for counter displayed along page menu item.
 */
trait CountablePage
{
    /**
     * @var \BlueChip\Security\Modules\Countable An object that provides the actual counter value to be displayed.
     */
    protected $counter;


    /**
     * Set counter that provides count to be displayed along main menu item for this page.
     *
     * @param \BlueChip\Security\Modules\Countable $counter
     */
    protected function setCounter(\BlueChip\Security\Modules\Countable $counter)
    {
        $this->counter = $counter;
    }


    /**
     * Reset count(er).
     */
    protected function resetCount()
    {
        $user = wp_get_current_user();
        // Update $user's last view time for this page.
        update_user_meta($user->ID, $this->getCounterUserMetaKey(), current_time('timestamp'));
    }


    /**
     * Get count to be displayed along with main menu item for this page.
     *
     * @return int
     */
    public function getCount()
    {
        $user = wp_get_current_user();

        $last_visit_timestamp = get_user_meta($user->ID, $this->getCounterUserMetaKey(), true);

        return empty($last_visit_timestamp) ? $this->counter->countAll() : $this->counter->countFrom($last_visit_timestamp);
    }


    /**
     * @return string
     */
    private function getCounterUserMetaKey()
    {
        return implode('/', [$this->getSlug(), 'last-visit']);
    }
}
