<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Admin;

/**
 * Basis (abstract) class for every admin page.
 */
abstract class AbstractPage
{
    /**
     * @var string Page slug (each inheriting class must define its own)
     */
    const SLUG = 'bc-security';

    /**
     * @var string Page title for menu
     */
    protected $menu_title;

    /**
     * @var string Page title for browser window
     */
    protected $page_title;


    /**
     * Output page content.
     */
    abstract public function render();


    /**
     * @return string Menu title of page.
     */
    public function getMenuTitle()
    {
        return $this->menu_title;
    }


    /**
     * @return string Browser title of page.
     */
    public function getPageTitle()
    {
        return $this->page_title;
    }


    /**
     * @return string Page slug.
     */
    public function getSlug()
    {
        return static::SLUG;
    }


    /**
     * @return string URL of admin page.
     */
    public function getUrl()
    {
        return static::getPageUrl();
    }


    /**
     * @return string URL of admin page.
     */
    public static function getPageUrl()
    {
        // Why static and not self? See: http://php.net/manual/en/language.oop5.late-static-bindings.php
        return add_query_arg('page', static::SLUG, admin_url('admin.php'));
    }


    /**
     * Register method to be run on page load.
     *
     * @link https://developer.wordpress.org/reference/hooks/load-page_hook/
     *
     * @param string $page_hook
     */
    public function setPageHook($page_hook)
    {
        add_action('load-' . $page_hook, [$this, 'loadPage']);
    }


    /**
     * Run on admin initialization.
     */
    public function init()
    {
        // By default do nothing.
    }


    /**
     * Run on page load.
     */
    public function loadPage()
    {
        // By default do nothing.
    }
}