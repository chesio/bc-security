<?php

declare(strict_types=1);

namespace BlueChip\Security\Core\Admin;

use BlueChip\Security\Modules\Countable;

/**
 * Provide information for counter displayed along page menu item.
 */
trait CountablePage
{
    /**
     * @var Countable An object that provides the actual counter value to be displayed.
     */
    protected Countable $counter;


    /**
     * Set counter that provides count to be displayed along main menu item for this page.
     *
     * @param Countable $counter
     */
    protected function setCounter(Countable $counter): void
    {
        $this->counter = $counter;
    }


    /**
     * Reset count(er).
     */
    protected function resetCount(): void
    {
        $user = wp_get_current_user();
        // Update $user's last view time for this page.
        update_user_meta($user->ID, $this->getCounterUserMetaKey(), \time());
    }


    /**
     * Get count to be displayed along with main menu item for this page.
     *
     * @return int
     */
    public function getCount(): int
    {
        $user = wp_get_current_user();

        $last_visit_timestamp = absint(get_user_meta($user->ID, $this->getCounterUserMetaKey(), true));

        return $last_visit_timestamp ? $this->counter->countFrom($last_visit_timestamp) : $this->counter->countAll();
    }


    /**
     * @return string
     */
    private function getCounterUserMetaKey(): string
    {
        return \implode('/', [$this->getSlug(), 'last-visit']);
    }
}
