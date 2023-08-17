<?php

namespace BlueChip\Security\Core\Admin;

use BlueChip\Security\Core\ListTable;

trait ListingPage
{
    private ListTable $list_table;

    private string $per_page_option_name;


    abstract protected function initListTable(): void;


    /**
     * @link https://developer.wordpress.org/reference/hooks/set-screen-option/
     */
    private function setPerPageOption(string $option_name): void
    {
        $this->per_page_option_name = $option_name;

        add_filter('set-screen-option', fn (mixed $screen_option, string $option, int $value): mixed => ($option === $option_name) ? $value : $screen_option, 10, 3);
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
