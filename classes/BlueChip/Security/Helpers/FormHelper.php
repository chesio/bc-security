<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Helpers;

abstract class FormHelper
{
    /**
     * Print <input type="checkbox" /> element.
     *
     * Unless "plain" is set as $args key, an extra hidden field with the same
     * name and empty (false-like) value is printed before checkbox - this way,
     * POST data contains value for checkbox even if it is left unchecked.
     * Note that this approach works thanks to the fact that PHP retains value
     * of the last key occurence in POST data when there are multiple occurences
     * of the same key (name); when checkbox is checked (and included in POST),
     * its value overwrites hidden field value.
     * See: http://stackoverflow.com/a/1992745
     *
     * @param array $args Required: label_for, name, value. Optional: plain.
     */
    public static function printCheckbox(array $args)
    {
        // Field properties
        $properties = [
            'type'      => 'checkbox',
            'value'     => 'true',
            'id'        => $args['label_for'],
            'name'      => $args['name'],
            'checked'   => boolval($args['value']),
        ];

        if (!isset($args['plain'])) {
            $hidden_properties = [
                'type' => 'hidden',
                // no value necessary - empty value is interpreted as false by PHP
                'name' => $args['name'],
            ];
            echo '<input ' . self::renderFieldProperties($hidden_properties) . '>';
        }
        echo '<input ' . self::renderFieldProperties($properties) . '>';

        self::printAppendix($args, true);
    }


    /**
     * Print <input type="hidden"> element.
     *
     * @param array $args
     */
    public static function printHiddenInput(array $args)
    {
        // Field properties
        $properties = [
            'type'      => 'hidden',
            'value'     => $args['value'],
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<input ' . self::renderFieldProperties($properties) . '>';
    }


    /**
     * Print <input type="number> element.
     *
     * @param array $args
     */
    public static function printNumberInput(array $args)
    {
        // Field properties
        $properties = [
            'type'      => 'number',
            'value'     => $args['value'],
            'id'        => $args['label_for'],
            'name'      => $args['name'],
            'class'     => 'small-text',
        ];

        echo '<input ' . self::renderFieldProperties($properties) . '>';

        self::printAppendix($args, true);
    }


    /**
     * Print <select /> element.
     *
     * @param array $args
     */
    public static function printSelect(array $args)
    {
        $properties = [
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<select ' . self::renderFieldProperties($properties) . '>';
        foreach ($args['options'] as $key => $value) {
            echo '<option value="' . esc_attr($key) . '"' . selected($key, $args['value'], false) . '>';
            echo esc_html($value);
            echo '</option>';
        }
        echo '</select>';

        self::printAppendix($args, true);
    }


    /**
     * Print <textarea /> element.
     *
     * Note: method expects the value argument ($args['value']) to be an array
     * (of lines).
     *
     * @param array $args
     */
    public static function printTextArea(array $args)
    {
        // Field properties
        $properties = [
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<textarea ' . self::renderFieldProperties($properties) . '>';
        echo esc_html(implode(PHP_EOL, $args['value']));
        echo '</textarea>';

        self::printAppendix($args, false);
    }


    /**
     * Join an array of properties into a string with key="value" pairs.
     * Escape values with esc_attr() function.
     *
     * @see esc_attr()
     *
     * @param array $properties
     * @return string
     */
    protected static function renderFieldProperties(array $properties): string
    {
        $filtered = array_filter(
            $properties,
            // Remove any false-like values (empty strings and false booleans) except for integers.
            function ($value) {
                return is_int($value) || (is_string($value) && !empty($value)) || (is_bool($value) && $value);
            }
        );
        // Map keys and values together as key=value
        $mapped = array_map(
            function ($key, $value) {
                // Boolean values are replaced with key name: checked => true ---> checked="checked"
                return sprintf('%s="%s"', $key, esc_attr(is_bool($value) ? $key : $value));
            },
            array_keys($filtered),
            array_values($filtered)
        );
        // Join all properties into single string
        return implode(' ', $mapped);
    }


    /**
     * Print optional appendix information provided by "description" or "append" keys in $args.
     * Note that "description" takes precedence over "append".
     *
     * @param array $args
     * @param bool $inline
     */
    protected static function printAppendix(array $args, bool $inline)
    {
        if (isset($args['description'])) {
            echo sprintf(
                '<%1$s class="description">%2$s</%1$s>',
                $inline ? 'span' : 'p',
                esc_html($args['description'])
            );
        } elseif (isset($args['append'])) {
            echo ($inline ? ' ' : '<br>') . esc_html($args['append']);
        }
    }
}
