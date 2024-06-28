<?php

declare(strict_types=1);

namespace BlueChip\Security\Core;

use ArrayAccess;

/**
 * Basis (abstract) class for setting objects.
 *
 * Every setting object's data are internally stored as single option in `wp_options` table using Settings API.
 *
 * @link https://developer.wordpress.org/plugins/settings/settings-api/
 *
 * @phpstan-implements ArrayAccess<string,mixed>
 */
abstract class Settings implements ArrayAccess
{
    /**
     * @var array<string,mixed> Default values for all settings. Descendant classes should override it.
     */
    protected const DEFAULTS = [];

    /**
     * @var array<string,callable> Sanitization routines for settings that cannot be just sanitized based on type of their default value.
     */
    protected const SANITIZERS = [];


    /**
     * @var array<string,mixed> Settings data (kind of cache for get_option() result).
     */
    private array $data;


    /**
     * @param string $option_name Option name under which to store the settings.
     */
    public function __construct(private string $option_name)
    {
        // Read settings from `wp_options` table and sanitize them right away using default values.
        $this->data = $this->sanitize(get_option($option_name, []), static::DEFAULTS);
    }


    //// ArrayAccess API ///////////////////////////////////////////////////////

    /**
     * Return true if there is any setting available under key $offset.
     *
     * @internal Implements ArrayAccess interface.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }


    /**
     * Retrieve setting under key $offset.
     *
     * @internal Implements ArrayAccess interface.
     *
     * @param string $offset
     *
     * @return mixed A null value is returned if $offset is not a valid key.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }


    /**
     * Update setting under key $offset with $value.
     *
     * @internal Implements ArrayAccess interface.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->update($offset, $value);
    }


    /**
     * Reset setting under key $offset to its default value.
     *
     * @internal Implements ArrayAccess interface.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->update($offset, null);
    }


    /**
     * Get option name.
     *
     * @return string
     */
    public function getOptionName(): string
    {
        return $this->option_name;
    }


    /**
     * Get option data.
     *
     * @return array<string,mixed>
     */
    public function get(): array
    {
        return $this->data;
    }


    /**
     * Sanitize $data and set them as option data.
     *
     * @param array<string,mixed> $data
     *
     * @return bool
     */
    public function set(array $data): bool
    {
        return $this->persist($this->sanitize($data));
    }


    /**
     * Reset option data to default values.
     *
     * @return bool
     */
    public function reset(): bool
    {
        return $this->persist(static::DEFAULTS);
    }


    /**
     * Set $autoload value of option.
     */
    public function setAutoload(bool $autoload): bool
    {
        return wp_set_option_autoload($this->option_name, $autoload);
    }


    /**
     * Remove the data from database (= hard reset).
     *
     * @return bool True if settings have been deleted, false otherwise.
     */
    public function destroy(): bool
    {
        return delete_option($this->option_name);
    }


    /**
     * Set the value of $data into local cache and also persist it in database.
     *
     * @param array<string,mixed> $data The data to be persisted.
     *
     * @return bool True if settings have been updated (= changed), false otherwise.
     */
    private function persist(array $data): bool
    {
        // Update local cache.
        $this->data = $data;

        // Persist new data.
        return update_option($this->option_name, $this->data);
    }


    //// Sanitization //////////////////////////////////////////////////////////

    /**
     * Sanitize $settings array: only keep known keys, provide default values for missing keys.
     *
     * @internal This method serves two purposes: it sanitizes data read from database and it sanitizes POST-ed data.
     * When using this method for database data sanitization, make sure that you provide default values for all settings.
     * When using this method for POST-ed data sanitization (ie. as `sanitize_callback` in `register_setting` function),
     * do not provide explicit $defaults as this method will implicitly use current values (data from DB that are already
     * sanitized) as defaults. This way POST-ed data do not need to be complete, because any missing settings will be
     * kept as they were.
     *
     * @param array<string,mixed> $settings Input data to sanitize.
     * @param array<string,mixed> $defaults [optional] If provided, used as default values for sanitization instead of local data.
     *
     * @return array<string,mixed>
     */
    public function sanitize(array $settings, array $defaults = []): array
    {
        // If no default values are provided, use data from internal cache as default values.
        $values = ($defaults === []) ? $this->get() : $defaults;

        // Loop over default values instead of provided $settings - this way only known keys are preserved.
        foreach ($values as $key => $default_value) {
            if (isset($settings[$key])) {
                // Sanitize the value by type first (= ensure the value has expected type).
                $value = self::sanitizeByType($settings[$key], $default_value);

                // If custom sanitizer for this setting key is provided...
                if (isset(static::SANITIZERS[$key])) {
                    // ...execute it on type-safe value.
                    $value = \call_user_func(static::SANITIZERS[$key], $value, $default_value);
                }

                $values[$key] = $value;
            }
        }

        return $values;
    }


    /**
     * Sanitize the $value according to type of $default value.
     *
     * @param mixed $value
     * @param mixed[]|bool|float|int|string $default
     *
     * @return mixed[]|bool|float|int|string
     */
    private static function sanitizeByType(mixed $value, array|bool|float|int|string $default): array|bool|float|int|string
    {
        if (\is_bool($default)) {
            return (bool) $value;
        } elseif (\is_float($default)) {
            return (float) $value;
        } elseif (\is_int($default)) {
            return (int) $value;
        } elseif (\is_array($default) && \is_string($value)) {
            return self::parseList($value);
        } else {
            return $value;
        }
    }


    /**
     * Parse a list of items separated by EOL character into array. Trim any empty lines (items).
     *
     * @param string|string[] $list
     *
     * @return string[]
     */
    private static function parseList(array|string $list): array
    {
        return \is_array($list) ? $list : \array_filter(\array_map('trim', \explode(PHP_EOL, $list)));
    }


    /**
     * Update setting under $name with $value and make the changes permanent.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return bool
     */
    private function update(string $name, mixed $value): bool
    {
        $data = $this->get();

        if (!isset($data[$name])) {
            // Cannot update, invalid setting name.
            return false;
        }

        if (null === $value) {
            // Null value unsets (resets) setting to default state
            unset($data[$name]);
        } else {
            // Any other value updates it
            $data[$name] = $value;
        }

        return $this->set($data);
    }


    /**
     * Execute provided $callback as soon as settings are updated and persisted.
     *
     * @internal When option is updated via Settings API (that is within request to `options.php`), internal cache
     * becomes out-dated. Normally, this is not a problem, because Settings API immediately redirects back to settings
     * page that initiated the request to `options.php` and the internal cache is populated anew. The only exception to
     * this processing order is this update hook - the hook is going to be executed in the scope of the `options.php`
     * request and thus the cache has to be updated before provided callback is fired.
     *
     * @param callable $callback Callback that accepts up to three parameters: $old_value, $value, $option_name.
     */
    public function addUpdateHook(callable $callback): void
    {
        add_action("update_option_{$this->option_name}", $this->updateOption(...), 10, 2);
        add_action("update_option_{$this->option_name}", $callback, 10, 3);
    }


    /**
     * @action https://developer.wordpress.org/reference/hooks/update_option_option/
     *
     * @param array<string,mixed> $old_value
     * @param array<string,mixed> $new_value
     */
    private function updateOption(array $old_value, array $new_value): void
    {
        $this->data = $new_value;
    }
}
