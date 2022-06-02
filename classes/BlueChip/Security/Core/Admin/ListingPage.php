<?php

namespace BlueChip\Security\Core\Admin;

trait ListingPage
{
    /**
     * @var \BlueChip\Security\Core\ListTable
     */
    private $list_table;

    /**
     * @var string
     */
    private $per_page_option_name;


    abstract protected function initListTable(): void;


    /**
     * @link https://developer.wordpress.org/reference/hooks/set-screen-option/
     *
     * @param string $option_name
     */
    private function setPerPageOption(string $option_name): void
    {
        $this->per_page_option_name = $option_name;

        add_filter('set-screen-option', function ($status, $option, $value) use ($option_name) {
            return ($option === $option_name) ? (int) $value : $status;
        }, 10, 3);
    }


    /**
     * @link https://developer.wordpress.org/reference/functions/add_screen_option/
     */
    private function addPerPageOption(): void
    {
        add_screen_option('per_page', [
            'label' => __('Records', 'bc-security'),
            'default' => 20,
            'option' => $this->per_page_option_name,
        ]);
    }
}
