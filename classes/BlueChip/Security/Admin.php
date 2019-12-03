<?php

namespace BlueChip\Security;

/**
 * Integration with WordPress admin area
 */
class Admin
{
    /**
     * @var string To use Settings API, user has to have manage_options capability.
     */
    const CAPABILITY = 'manage_options';

    /**
     * @var string Plugin dashboard menu icon
     */
    const ICON = 'dashicons-shield-alt';


    /**
     * @var \BlueChip\Security\Core\Admin\AbstractPage[]
     */
    private $pages = [];


    /**
     * Initialize admin area of the plugin.
     *
     * @param string $plugin_filename
     * @return self
     */
    public function init(string $plugin_filename): self
    {
        add_action('admin_menu', [$this, 'makeAdminMenu']);
        add_action('admin_init', [$this, 'initAdminPages']);
        add_filter('plugin_action_links_' . plugin_basename($plugin_filename), [$this, 'filterActionLinks']);
        return $this;
    }


    /**
     * Add a page to plugin dashboard menu.
     *
     * @param \BlueChip\Security\Core\Admin\AbstractPage $page
     * @return self
     */
    public function addPage(Core\Admin\AbstractPage $page): self
    {
        $this->pages[$page->getSlug()] = $page;
        return $this;
    }


    /**
     * @action https://developer.wordpress.org/reference/hooks/admin_init/
     */
    public function initAdminPages()
    {
        foreach ($this->pages as $page) {
            $page->initPage();
        }
    }


    /**
     * Make plugin menu.
     *
     * @action https://developer.wordpress.org/reference/hooks/admin_menu/
     */
    public function makeAdminMenu()
    {
        if (empty($this->pages)) {
            // No pages registered = no pages (no menu) to show.
            return;
        }

        // First registered page acts as main page:
        $main_page = \reset($this->pages);

        // Add (main) menu page
        add_menu_page(
            '', // obsolete as soon as page has subpages
            _x('BC Security', 'Dashboard menu item name', 'bc-security'),
            self::CAPABILITY,
            $main_page->getSlug(),
            '', // obsolete as soon as page has subpages
            self::ICON
        );

        // Add subpages
        foreach ($this->pages as $page) {
            $page_hook = add_submenu_page(
                $main_page->getSlug(),
                $page->getPageTitle(),
                $page->getMenuTitle() . $this->renderCounter($page),
                self::CAPABILITY,
                $page->getSlug(),
                [$page, 'printContents']
            );
            if ($page_hook) {
                $page->setPageHook($page_hook);
            }
        }
    }


    /**
     * Filter plugin action links: append link to setup page only.
     *
     * @filter https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
     *
     * @param array $links
     * @return array
     */
    public function filterActionLinks(array $links): array
    {
        if (current_user_can(self::CAPABILITY) && isset($this->pages['bc-security-setup'])) {
            $links[] = \sprintf(
                '<a href="%s">%s</a>',
                $this->pages['bc-security-setup']->getUrl(),
                esc_html($this->pages['bc-security-setup']->getMenuTitle())
            );
        }
        return $links;
    }


    /**
     * Format counter indicator for menu title for given $page.
     *
     * @param \BlueChip\Security\Core\Admin\AbstractPage $page
     * @return string
     */
    private function renderCounter(Core\Admin\AbstractPage $page): string
    {
        // Counter is optional.
        return \method_exists($page, 'getCount') && !empty($count = $page->getCount())
            ? \sprintf(' <span class="awaiting-mod"><span>%d</span></span>', number_format_i18n($count))
            : ''
        ;
    }
}
