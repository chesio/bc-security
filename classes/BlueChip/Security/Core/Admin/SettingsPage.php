<?php

namespace BlueChip\Security\Core\Admin;

/**
 * Common settings API boilerplate for admin pages.
 */
trait SettingsPage
{
    /**
     * @var string Option group
     */
    protected $option_group;

    /**
     * @var string Option name
     */
    protected $option_name;

    /**
     * @var string|null Recent page serves as default $page for add_settings_field() and add_settings_section() functions.
     */
    protected $recent_page = null;

    /**
     * @var string|null Recent section serves as default $section for add_settings_field() function.
     */
    protected $recent_section = null;

    /**
     * @var \BlueChip\Security\Core\Settings Object with actual settings.
     */
    protected $settings;


    /**
     * @link https://codex.wordpress.org/Settings_API
     *
     * @param \BlueChip\Security\Core\Settings $settings
     */
    protected function useSettings(\BlueChip\Security\Core\Settings $settings): void
    {
        // Remember the settings.
        $this->settings = $settings;
        $this->option_name = $settings->getOptionName();
        $this->option_group = \md5($this->option_name);
    }


    /**
     * Display settings errors via admin notices.
     */
    public function displaySettingsErrors(): void
    {
        add_action('admin_notices', 'settings_errors');
    }


    /**
     * Register setting.
     */
    public function registerSettings(): void
    {
        register_setting($this->option_group, $this->option_name, [$this->settings, 'sanitize']);
    }


    /**
     * Unregister setting.
     */
    public function unregisterSettings(): void
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
    public function setSettingsPage(string $page): void
    {
        $this->recent_page = $page;
    }


    //// Helpers ///////////////////////////////////////////////////////////////

    /**
     * Get base properties (arguments) for setting field with given $key.
     *
     * @param string $key
     * @param mixed $value [optional] Value to use instead of current value of setting with $key.
     *
     * @return array<string,mixed>
     */
    protected function getFieldBaseProperties(string $key, $value = null): array
    {
        return [
            'label_for' => \sprintf('%s-%s', $this->option_name, $key), // "label_for" is WP reserved name
            'key' => $key,
            'name' => \sprintf('%s[%s]', $this->option_name, $key),
            'value' => null === $value ? $this->settings[$key] : $value,
        ];
    }


    //// WP wrappers ///////////////////////////////////////////////////////////

    /**
     * Helper function that wraps call to add_settings_section().
     *
     * @see add_settings_section()
     *
     * @param string $section
     * @param string $title
     * @param callable|null $callback [optional]
     */
    public function addSettingsSection(string $section, string $title, ?callable $callback = null): void
    {
        if (!\is_string($this->recent_page)) {
            _doing_it_wrong(__METHOD__, 'No recent page set yet!', '0.1.0');
            return;
        }

        // Remember the most recent section.
        $this->recent_section = $section;
        // Add new section to most recent page.
        add_settings_section($section, $title, $callback, $this->recent_page);
    }


    /**
     * Helper function that wraps call(s) to add_settings_field().
     *
     * @see add_settings_field()
     *
     * @param string $key Key of the field (must be proper key from Settings)
     * @param string $title Title of the field
     * @param callable $callback Callback that produces form input for the field
     * @param array<string,mixed> $args [Optional] Any extra arguments for $callback function
     */
    public function addSettingsField(string $key, string $title, callable $callback, array $args = []): void
    {
        if (!\is_string($this->recent_page)) {
            _doing_it_wrong(__METHOD__, 'No recent page set yet!', '0.1.0');
            return;
        }

        if (!\is_string($this->recent_section)) {
            _doing_it_wrong(__METHOD__, 'No recent section added yet!', '0.1.0');
            return;
        }

        add_settings_field(
            $key, // $id
            $title,
            $callback,
            $this->recent_page, // $page
            $this->recent_section, // $section
            \array_merge($args, $this->getFieldBaseProperties($key)) // $args
        );
    }


    //// Printers //////////////////////////////////////////////////////////////

    /**
     * Output nonce, action and other hidden fields.
     */
    public function printSettingsFields(): void
    {
        settings_fields($this->option_group);
    }


    /**
     * Output visible form fields.
     */
    public function printSettingsSections(): void
    {
        if (!\is_string($this->recent_page)) {
            _doing_it_wrong(__METHOD__, 'No recent page set!', '0.1.0');
            return;
        }

        do_settings_sections($this->recent_page);
    }


    /**
     * Output form for settings manipulation.
     */
    protected function printSettingsForm(): void
    {
        echo '<form method="post" action="' . admin_url('options.php') . '">';

        // Output nonce, action and other hidden fields...
        $this->printSettingsFields();
        // ... visible fields ...
        $this->printSettingsSections();
        // ... and finally the submit button :)
        submit_button();

        echo '</form>';
    }
}
