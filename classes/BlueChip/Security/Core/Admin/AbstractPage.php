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
     * @var string Name of nonce used for any custom actions on admin pages
     */
    const NONCE_NAME = '_wpnonce';

    /**
     * @var string Page title for menu
     */
    protected $menu_title;

    /**
     * @var string Page title for browser window
     */
    protected $page_title;


    /**
     * Output page contents.
     */
    abstract public function printContents();


    /**
     * @return string Menu title of page.
     */
    public function getMenuTitle(): string
    {
        return $this->menu_title;
    }


    /**
     * @return string Browser title of page.
     */
    public function getPageTitle(): string
    {
        return $this->page_title;
    }


    /**
     * @return string Page slug.
     */
    public function getSlug(): string
    {
        return static::SLUG;
    }


    /**
     * @return string URL of admin page.
     */
    public function getUrl(): string
    {
        return static::getPageUrl();
    }


    /**
     * @return string URL of admin page.
     */
    public static function getPageUrl(): string
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
    public function setPageHook(string $page_hook)
    {
        add_action('load-' . $page_hook, [$this, 'loadPage']);
    }


    /**
     * Run on admin initialization (in `admin_init` hook).
     */
    public function initPage()
    {
        // By default do nothing.
    }


    /**
     * Run on page load.
     *
     * @action https://developer.wordpress.org/reference/hooks/load-page_hook/
     */
    public function loadPage()
    {
        // By default do nothing.
    }
}
