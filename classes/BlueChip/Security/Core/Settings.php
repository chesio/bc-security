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
    public function __construct($option_name)
    {
        // Read settings from wp_options table and sanitize them right away.
        $this->option_name = $option_name;
        $this->data = $this->sanitize(get_option($option_name, []));
    }


    /**
     * Get value of setting under key $name.
     *
     * @param string $name
     * @return mixed A null value is returned if $name is not a valid key.
     */
    public function __get($name)
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
    public function __set($name, $value)
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
    public function offsetExists($offset)
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
    public function getOptionName()
    {
        return $this->option_name;
    }


    /**
     * Sanitize $settings array: only return known keys, provide default values for missing keys.
     *
     * @param array $settings
     * @return array
     */
    abstract public function sanitize(array $settings);


    /**
     * Parse a list of items separated by EOL character into array. Trim any empty lines (items).
     *
     * @param array|string $list
     * @return array
     */
    protected function parseList($list)
    {
        return is_array($list) ? $list : array_filter(array_map('trim', explode(PHP_EOL, $list)));
    }


    /**
     * Update setting under $name with $value. Store update values in DB.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    protected function update($name, $value)
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
        // Update DB value
        return update_option($this->option_name, $this->data);
    }
}
