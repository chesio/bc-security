<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core;

/**
 * Basis (abstract) class for every admin page.
 */
abstract class AdminPage
{
    /**
     * @var string
     */
    public $menu_title;

    /**
     * @var string
     */
    public $page_title;

    /**
     * @var string
     */
    public $slug;


    /**
     * Output page content
     */
    abstract public function render();


    /**
     * Helper method to get URL of admin page identified by $slug.
     * @param string $slug
     * @return string
     */
    public static function getPageUrl($slug)
    {
        return add_query_arg(['page' => $slug,], admin_url('admin.php'));
    }


    /**
     * @return string URL of admin page.
     */
    public function getUrl()
    {
        return self::getPageUrl($this->slug);
    }


    /**
     * @param string $hook
     */
    public function setHook($hook)
    {
        add_action('load-' . $hook, [$this, 'loadPage']);
    }


    /**
     * Run on page load.
     */
    public function loadPage()
    {
        // By default do nothing.
    }
}
