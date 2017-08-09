<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core;

/**
 * Base class for all list tables in plugin.
 */
abstract class ListTable extends \WP_List_Table
{
    /**
     * @var string URL of admin page where the list table is displayed
     */
    protected $url;

    /**
     * @var string Sorting direction (asc or desc)
     */
    protected $order = 'desc';

    /**
     * @var string Sorting column
     */
    protected $order_by = 'id';

    /**
     * @var int Number of items per page.
     */
    protected $items_per_page;


    /**
     * @param string $url
     * @param string $per_page_option_name
     * @param array $args
     */
    public function __construct($url, $per_page_option_name, array $args = [])
    {
        $default_args = [
            'singular' => __('Record', 'bc-security'),
            'plural' => __('Records', 'bc-security'),
            'ajax' => false,
        ];

        parent::__construct(array_merge($default_args, $args));

        $this->url = $url;
        $this->items_per_page = $this->get_items_per_page($per_page_option_name);

        $order_by = filter_input(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING);
        if (in_array($order_by, $this->get_sortable_columns(), true)) {
            $this->order_by = $order_by;
            $this->url = add_query_arg('orderby', $order_by, $this->url);
        }

        $order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING);
        if ($order === 'asc' || $order === 'desc') {
            $this->order = $order;
            $this->url = add_query_arg('order', $order, $this->url);
        }
    }


    /**
     * Display (dismissible) admin notice informing user that an action has been performed successfully.
     */
    public function displayNotice($query_arg, $single, $plural)
    {
        $result = filter_input(INPUT_GET, $query_arg, FILTER_VALIDATE_INT);
        if (is_int($result) && ($result > 0)) {
            AdminNotices::add(
                _n($single, $plural, $result, 'bc-security'),
                AdminNotices::SUCCESS
            );
            add_filter('removable_query_args', function ($removable_query_args) use ($query_arg) {
                $removable_query_args[] = $query_arg;
                return $removable_query_args;
            });
        }
    }


    /**
     * Return content for "checkbox" column.
     *
     * @param array $item
     * @return string
     */
    public function column_cb($item) // @codingStandardsIgnoreLine
    {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', $item['id']);
    }


    /**
     * Return value for default columns (with no extra value processing).
     *
     * @param array $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name) // @codingStandardsIgnoreLine
    {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }


    /**
     * Output "no items" message.
     */
    public function no_items() // @codingStandardsIgnoreLine
    {
        esc_html_e('No records to display.', 'bc-security');
    }
}
