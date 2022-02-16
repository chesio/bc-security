<?php

namespace BlueChip\Security\Helpers;

abstract class FormHelper
{
    /**
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/textarea
     * @var int Default value of "cols" attribute of <textarea> element.
     */
    private const TEXTAREA_COLS_DEFAULT_VALUE = 20;

    /**
     * @var int Maximum for content-based value of "rows" attribute of <textarea> element.
     */
    private const TEXTAREA_ROWS_MAXIMUM_VALUE = 20;

    /**
     * @var int Minimum for content-based value of "rows" attribute of <textarea> element.
     */
    private const TEXTAREA_ROWS_MINIMUM_VALUE = 4;


    /**
     * Print <input type="checkbox"> element.
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
     * @param array $args Required: label_for, name, value. Optional: class, plain.
     */
    public static function printCheckbox(array $args): void
    {
        // Field properties
        $properties = [
            'class'     => $args['class'] ?? '',
            'type'      => 'checkbox',
            'value'     => 'true',
            'id'        => $args['label_for'],
            'name'      => $args['name'],
            'checked'   => (bool) $args['value'],
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
     * @param array $args Required: label_for, name, value. Optional: class.
     */
    public static function printHiddenInput(array $args): void
    {
        // Field properties
        $properties = [
            'class'     => $args['class'] ?? '',
            'type'      => 'hidden',
            'value'     => $args['value'],
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<input ' . self::renderFieldProperties($properties) . '>';
    }


    /**
     * Print <input type="number"> element.
     *
     * @param array $args Required: label_for, name, value. Optional: class.
     */
    public static function printNumberInput(array $args): void
    {
        // Field properties
        $properties = [
            'class'     => $args['class'] ?? 'small-text',
            'type'      => 'number',
            'value'     => $args['value'],
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<input ' . self::renderFieldProperties($properties) . '>';

        self::printAppendix($args, true);
    }


    /**
     * Print <input type="text"> element.
     *
     * @param array $args Required: label_for, name, value. Optional: class.
     */
    public static function printTextInput(array $args): void
    {
        // Field properties
        $properties = [
            'class'     => $args['class'] ?? 'regular-text',
            'type'      => 'text',
            'value'     => $args['value'],
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<input ' . self::renderFieldProperties($properties) . '>';

        self::printAppendix($args, true);
    }


    /**
     * Print <select /> element.
     *
     * @param array $args Required: label_for, name, value. Optional: class.
     */
    public static function printSelect(array $args): void
    {
        $properties = [
            'class'     => $args['class'] ?? '',
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
     * Note: method expects the value argument `$args['value']` to be an array (of lines).
     *
     * @param array $args Required: label_for, name, value. Optional: class, cols, rows.
     */
    public static function printTextArea(array $args): void
    {
        // Field properties
        $properties = [
            'class'     => $args['class'] ?? '',
            'id'        => $args['label_for'],
            'name'      => $args['name'],
            'cols'      => $args['cols'] ?? self::TEXTAREA_COLS_DEFAULT_VALUE,
            'rows'      => $args['rows'] ?? \max(\min(\count($args['value']), self::TEXTAREA_ROWS_MAXIMUM_VALUE), self::TEXTAREA_ROWS_MINIMUM_VALUE),
        ];

        echo '<textarea ' . self::renderFieldProperties($properties) . '>';
        echo esc_html(\implode(PHP_EOL, $args['value']));
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
        $filtered = \array_filter(
            $properties,
            // Remove any false-like values (empty strings and false booleans) except for integers and floats.
            function ($value) {
                return \is_int($value)
                    || \is_float($value)
                    || (\is_string($value) && $value !== '')
                    || (\is_bool($value) && $value)
                ;
            }
        );
        // Map keys and values together as key=value
        $mapped = \array_map(
            function ($key, $value) {
                // Boolean values are replaced with key name: checked => true ---> checked="checked"
                return \sprintf('%s="%s"', $key, esc_attr(\is_bool($value) ? $key : $value));
            },
            \array_keys($filtered),
            \array_values($filtered)
        );
        // Join all properties into single string
        return \implode(' ', $mapped);
    }


    /**
     * Print optional appendix information provided by "description" or "append" keys in $args.
     * Note that "description" takes precedence over "append".
     *
     * @param array $args
     * @param bool $inline
     */
    protected static function printAppendix(array $args, bool $inline): void
    {
        if (isset($args['description'])) {
            echo \sprintf(
                '<%1$s class="description">%2$s</%1$s>',
                $inline ? 'span' : 'p',
                esc_html($args['description'])
            );
        } elseif (isset($args['append'])) {
            echo ($inline ? ' ' : '<br>') . esc_html($args['append']);
        }
    }
}
