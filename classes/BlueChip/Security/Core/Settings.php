<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core;

/**
 * Basis (abstract) class for setting objects
 */
abstract class Settings implements \ArrayAccess
{
    /**
     * @var string Option name under which settings are stored
     */
    private $option_name;

    /**
     * @var array Cache for get_option() result.
     */
    protected $data;


    /**
     * @param string $option_name
     */
    public function __construct(string $option_name)
    {
        $this->option_name = $option_name;
        // Read settings from wp_options table and sanitize them right away using default values.
        $this->data = $this->sanitize(get_option($option_name, []), $this->getDefaults());
    }


    /**
     * Get value of setting under key $name.
     *
     * @param string $name
     * @return mixed A null value is returned if $name is not a valid key.
     */
    public function __get(string $name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } else {
            _doing_it_wrong(__METHOD__, sprintf('Unknown settings key "%s"', $name), '0.1.0');
            return null;
        }
    }


    /**
     * Set value of setting under key $name to $value.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        if (isset($this->data[$name])) {
            $this->update($name, $value);
        } else {
            _doing_it_wrong(__METHOD__, sprintf('Unknown settings key "%s"', $name), '0.1.0');
        }
    }


    //// ArrayAccess API ///////////////////////////////////////////////////////

    /**
     * Return true, if there is any setting available under key $offset.
     *
     * @internal Implements ArrayAccess interface.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }


    /**
     * Retrieve setting under key $offset.
     *
     * @internal Implements ArrayAccess interface.
     *
     * @param string $offset
     * @return mixed A null value is returned if $offset is not a valid key.
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }


    /**
     * Update setting under key $offset with $value.
     *
     * @internal Implements ArrayAccess interface.
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->update($offset, $value);
    }


    /**
     * Reset setting under key $offset to its default value.
     *
     * @internal Implements ArrayAccess interface.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
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
     * Return array with default values.
     *
     * @internal Default values determine both valid settings keys and expected type of every value.
     *
     * @return array
     */
    abstract public function getDefaults(): array;


    /**
     * Sanitize $settings array: only keep known keys, provide default values for missing keys.
     *
     * @param array $settings Items to sanitize.
     * @param array $defaults [optional] If provided, used as default values for sanitization instead of local data.
     * @return array
     */
    public function sanitize(array $settings, array $defaults = []): array
    {
        //
        $values = empty($defaults) ? $this->data : $defaults;

        foreach ($values as $key => $default_value) {
            $values[$key] = isset($settings[$key])
                ? $this->sanitizeSingleValue($key, $settings[$key], $default_value)
                : $default_value
            ;
        }

        return $values;
    }


    /**
     * Sanitize single $value according to type of $default value.
     *
     * @internal If a particular setting needs a special sanitization, simply override this method.
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    public function sanitizeSingleValue(string $key, $value, $default)
    {
        if (is_bool($default)) {
            return boolval($value);
        } elseif (is_int($default)) {
            return intval($value);
        } elseif (is_array($default)) {
            return $this->parseList($value);
        } else {
            return $value;
        }
    }


    /**
     * Parse a list of items separated by EOL character into array. Trim any empty lines (items).
     *
     * @param array|string $list
     * @return array
     */
    protected function parseList($list): array
    {
        return is_array($list) ? $list : array_filter(array_map('trim', explode(PHP_EOL, $list)));
    }


    /**
     * Persist the value of data into database.
     *
     * @return bool
     */
    protected function persist(): bool
    {
        return update_option($this->option_name, $this->data);
    }


    /**
     * Execute provided $callback as soon as settings are updated and persisted.
     *
     * @param callable $callback Callback that accepts up to three parameters: $old_value, $value, $option_name.
     */
    public function addUpdateHook(callable $callback)
    {
        add_action("update_option_{$this->option_name}", $callback, 10, 3);
    }


    /**
     * Update setting under $name with $value. Store update values in DB.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    protected function update(string $name, $value): bool
    {
        if (!isset($this->data[$name])) {
            // Cannot update, invalid setting name.
            return false;
        }

        $data = $this->data;

        if (is_null($value)) {
            // Null value unsets (resets) setting to default state
            unset($data[$name]);
        } else {
            // Any other value updates it
            $data[$name] = $value;
        }

        // Sanitize new value and update cache
        $this->data = $this->sanitize($data);
        // Make changes permanent.
        return $this->persist();
    }
}
