<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security;

/**
 * Integration with WordPress admin area
 */
class Admin
{
    /**
     * To use Settings API, user has to have manage_options capability.
     */
    const CAPABILITY = 'manage_options';

    /**
     * Plugin dashboard menu icon
     */
    const ICON = 'dashicons-shield-alt';


    /**
     * @var array
     */
    private $pages = [];


    /**
     * Initialize admin area of the plugin.
     *
     * @return \BlueChip\Security\Admin
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'makeAdminMenu']);
        add_filter('plugin_action_links_' . plugin_basename(BC_SECURITY_PLUGIN_FILE), [$this, 'filterActionLinks']);
        return $this;
    }


    /**
     * Add a page to plugin dashboard menu.
     *
     * @param \BlueChip\Security\Core\AdminPage $page
     * @return \BlueChip\Security\Admin
     */
    public function addPage(Core\AdminPage $page)
    {
        $this->pages[$page->slug] = $page;
        return $this;
    }


    /**
     * Make plugin menu - this method is hooked to `admin_menu` hook.
     */
    public function makeAdminMenu()
    {
        if (empty($this->pages)) {
            // No pages registered = no pages (no menu) to show.
            return;
        }

        // First registered page acts as main page:
        $main_page = reset($this->pages);

        // Add (main) menu page
        add_menu_page(
            '', // obsolete as soon as page has subpages
            _x('BC Security', 'Dashboard menu item name', 'bc-security'),
            self::CAPABILITY,
            $main_page->slug,
            '', // obsolete as soon as page has subpages
            self::ICON
        );

        // Add subpages
        foreach ($this->pages as $page) {
            $hook = add_submenu_page(
                $main_page->slug,
                $page->page_title,
                $page->menu_title,
                self::CAPABILITY,
                $page->slug,
                [$page, 'render']
            );
            if ($hook) {
                $page->setHook($hook);
            }
        }
    }


    /**
     * Filter plugin action links: append link to setup page only.
     *
     * @param array $links
     * @return array
     */
    public function filterActionLinks(array $links)
    {
        if (current_user_can(self::CAPABILITY) && isset($this->pages['bc-security-setup'])) {
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                $this->pages['bc-security-setup']->getUrl(),
                esc_html($this->pages['bc-security-setup']->menu_title)
            );
        }
        return $links;
    }
}
