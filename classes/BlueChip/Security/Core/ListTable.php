<?php

declare(strict_types=1);

namespace BlueChip\Security\Core;

use BlueChip\Security\Helpers\AdminNotices;
use BlueChip\Security\Helpers\MySQLDateTime;

/**
 * Base class for all list tables in plugin.
 */
abstract class ListTable extends \WP_List_Table
{
    /**
     * @var string Format of date and time columns in list tables
     */
    protected const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var string Nonce name used for actions in all tables
     */
    protected const NONCE_NAME = '_wpnonce';

    /**
     * @var string URL of admin page where the list table is displayed
     */
    protected string $url;

    /**
     * @var string Sorting direction (asc or desc)
     */
    protected string $order = 'desc';

    /**
     * @var string Sorting column
     */
    protected string $order_by = 'id';

    /**
     * @var int Number of items per page.
     */
    protected int $items_per_page;


    /**
     * @param string $url URL of admin page where list table is displayed.
     * @param string $per_page_option_name Option name for "per page" screen option.
     * @param array<string,mixed> $args
     */
    public function __construct(string $url, string $per_page_option_name, array $args = [])
    {
        $default_args = [
            'singular' => __('Record', 'bc-security'),
            'plural' => __('Records', 'bc-security'),
            'ajax' => false,
        ];

        parent::__construct(\array_merge($default_args, $args));

        $this->url = $url;
        $this->items_per_page = $this->get_items_per_page($per_page_option_name);

        $order_by = \filter_input(INPUT_GET, 'orderby');
        if (\is_string($order_by) && \in_array($order_by, $this->get_sortable_columns(), true)) {
            $this->order_by = $order_by;
            $this->url = add_query_arg('orderby', $order_by, $this->url);
        }

        $order = \filter_input(INPUT_GET, 'order');
        if ($order === 'asc' || $order === 'desc') {
            $this->order = $order;
            $this->url = add_query_arg('order', $order, $this->url);
        }
    }


    /**
     * Display (dismissible) admin notice informing user that an action has been performed successfully.
     *
     * @param string $action Name of query string argument that indicates number of items affected by action.
     * @param string $single The text to be used in notice if action affected single item.
     * @param string $plural The text to be used in notice if action affected multiple items.
     */
    protected function displayNotice(string $action, string $single, string $plural): void
    {
        // Have any items been affected by given action?
        $result = \filter_input(INPUT_GET, $action, FILTER_VALIDATE_INT);
        if (\is_int($result) && ($result > 0)) {
            AdminNotices::add(
                _n($single, $plural, $result, 'bc-security'),
                AdminNotices::SUCCESS
            );
            add_filter('removable_query_args', function (array $removable_query_args) use ($action): array {
                $removable_query_args[] = $action;
                return $removable_query_args;
            });
        }
    }


    /**
     * Return HTML for specified row action link.
     *
     * @param string $action
     * @param int $id
     * @param string $class
     * @param string $label
     *
     * @return string
     */
    protected function renderRowAction(string $action, int $id, string $class, string $label): string
    {
        return \sprintf(
            '<span class="' . $class . '"><a href="%s">%s</a></span>',
            wp_nonce_url(
                add_query_arg(
                    ['action' => $action, 'id' => $id],
                    $this->url
                ),
                \sprintf('%s:%s', $action, $id),
                self::NONCE_NAME
            ),
            esc_html($label)
        );
    }


    /**
     * Return content for "checkbox" column.
     *
     * @param array<string,string> $item
     *
     * @return string
     */
    public function column_cb($item) // phpcs:ignore
    {
        return \sprintf('<input type="checkbox" name="ids[]" value="%d" />', (int) $item['id']);
    }


    /**
     * Return column contents without any extra processing.
     *
     * @param array<string,string> $item
     * @param string $column_name
     *
     * @return string
     */
    public function column_default($item, $column_name) // phpcs:ignore
    {
        return $item[$column_name] ?? '';
    }


    /**
     * Display datetime database fields in local time.
     *
     * @param string $datetime Datetime string retrieved from database.
     *
     * @return string Date and time of $datetime formatted in local time. Empty string if $datetime could not be parsed.
     */
    public function formatDateAndTime(string $datetime): string
    {
        $timestamp = MySQLDateTime::parseTimestamp($datetime);
        return ($timestamp !== null) ? (wp_date(self::DATETIME_FORMAT, $timestamp) ?: '') : '';
    }


    /**
     * Output "no items" message.
     *
     * @return void
     */
    public function no_items() // phpcs:ignore
    {
        esc_html_e('No records to display.', 'bc-security');
    }
}
