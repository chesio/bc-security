<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Helpers;

/**
 * Helper for WordPress Settings API that provides methods for adding setting
 * fields and sections to settings page.
 *
 * @link https://kovshenin.com/2012/the-wordpress-settings-api/
 */
class SettingsHelper
{
    /**
     * General settings page slug-name - usable as $page arg to set_settings_page().
     */
    const SETTINGS_GENERAL      = 'general';

    /**
     * Writing settings page slug-name - usable as $page arg to set_settings_page().
     */
    const SETTINGS_WRITING      = 'writing';

    /**
     * Reading settings page slug-name - usable as $page arg to set_settings_page().
     */
    const SETTINGS_READING      = 'reading';

    /**
     * Discussion settings page slug-name - usable as $page arg to set_settings_page().
     */
    const SETTINGS_DISCUSSION   = 'discussion';

    /**
     * Media settings page slug-name - usable as $page arg to set_settings_page().
     */
    const SETTINGS_MEDIA        = 'media';

    /**
     * Permalink settings page slug-name - usable as $page arg to set_settings_page().
     */
    const SETTINGS_PERMALINK    = 'permalink';


    /**
     * @var string Option group
     */
    protected $option_group;

    /**
     * @var string Option name
     */
    protected $option_name;

    /**
     * @var string Most recently set page serves as $page value for add_settings_section() and add_settings_field() functions
     */
    protected $recent_page;

    /**
     * @var string Most recently added section serves as $section value for add_settings_field() function
     */
    protected $recent_section;

    /**
     * @var \BlueChip\Security\Core\Settings Object with actual settings.
     */
    protected $settings;


    /**
     * Settings helper needs to know the settings only.
     * @param \BlueChip\Security\Core\Settings $settings
     *
     * @link https://codex.wordpress.org/Settings_API
     */
    public function __construct(\BlueChip\Security\Core\Settings $settings)
    {
        // Remember the settings
        $this->settings = $settings;
        $this->option_name = $settings->getOptionName();
        $this->option_group = md5($this->option_name);
    }


    /**
     * Register setting
     */
    public function register()
    {
        register_setting($this->option_group, $this->option_name, [$this->settings, 'sanitize']);
    }


    /**
     * Unregister setting
     */
    public function unregister()
    {
        unregister_setting($this->option_group, $this->option_name);
    }


    /**
     * Set $page as recent page, ie. value of $page argument for
     * add_settings_field(), add_settings_section() and do_settings_sections()
     * functions. To add setting sections and fields to built-in pages, pass one
     * of SETTINGS_* constants as $page.
     * @param string $page
     */
    public function setSettingsPage($page)
    {
        $this->recent_page = $page;
    }


	//// WP wrappers ///////////////////////////////////////////////////////////

    /**
     * Helper function that wraps call to add_settings_section().
     * @param string $section
     * @param string $title
     * @param callback $callback
     * @see add_settings_section()
     */
    public function addSettingsSection($section, $title, $callback = null)
    {
        if (!is_string($this->recent_page)) {
            _doing_it_wrong(__METHOD__, __('No recent page set yet!', 'bc-security'), '0.1');
            return;
        }

        // Remember the most recent section
        $this->recent_section = $section;
        // Add new section to most recent page
        add_settings_section($section, $title, $callback, $this->recent_page);
    }


    /**
     * Helper function that wraps call(s) to add_settings_field()
     *
     * @param string $key Key of the field (must be proper key from Settings)
     * @param string $title Title of the field
     * @param callback $callback Function that fills the field with the desired form inputs
     * @param array $args [Optional] Any extra arguments for $callback function
     * @see add_settings_field()
     */
    public function addSettingsField($key, $title, $callback, $args = array())
    {
        if (!is_string($this->recent_page)) {
            _doing_it_wrong(__METHOD__, __('No recent page set yet!', 'bc-security'), '0.1');
            return;
        }

        if (!is_string($this->recent_section)) {
            _doing_it_wrong(__METHOD__, __('No recent section added yet!', 'bc-security'), '0.1');
            return;
        }

        add_settings_field(
            $key, // $id
            $title,
            $callback,
            $this->recent_page, // $page
            $this->recent_section, // $section
            array_merge($args, [ // $args
                'label_for' => sprintf('%s-%s', $this->option_name, $key), // "label_for" is WP reserved name
                'key' => $key,
                'name' => sprintf('%s[%s]', $this->option_name, $key),
                'value' => $this->settings[$key],
            ])
        );

    }


    //// Printers //////////////////////////////////////////////////////////////

    /**
     * Print settings form with settings from recent page (ie. $page that has
     * been set as last via set_settings_page()).
     */
    public function renderForm()
    {
        if (!is_string($this->recent_page)) {
            _doing_it_wrong(__METHOD__, __('No recent page set!', 'bc-security'), '0.1');
            return;
        }

        echo '<form method="post" action="options.php">';
        // Nonce, action and other hidden fields...
        settings_fields($this->option_group);
        // Visible fields
        do_settings_sections($this->recent_page);
        // :)
        submit_button();
        //
        echo '</form>';
    }
}
