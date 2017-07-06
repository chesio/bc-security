<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Helpers;


class FormHelper
{
	/**
	 * Render <input type="checkbox" /> element.
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
	public function renderCheckbox(array $args)
    {
		// Field properties
		$properties = [
			'type'		=> 'checkbox',
			'value'		=> 'true',
			'id'		=> $args['label_for'],
			'name'		=> $args['name'],
			'checked'	=> boolval($args['value']),
		];

		if (!isset($args['plain'])) {
			$hidden_properties = [
				'type' => 'hidden',
				// no value necessary - empty value is interpreted as false by PHP
				'name' => $args['name'],
			];
			echo '<input ' . $this->printFieldProperties($hidden_properties) . '>';
		}
		echo '<input ' . $this->printFieldProperties($properties) . '>';
	}


    /**
     * Render <input type="number> element.
     * @param array $args
     */
    public function renderNumberInput(array $args)
    {
        // Field properties
        $properties = [
            'type'      => 'number',
            'value'     => $args['value'],
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<input ' . $this->printFieldProperties($properties) . '>';

        if (isset($args['append'])) {
            echo ' ' . esc_html($args['append']);
        }
    }


    /**
     * Render <select /> element.
     * @param array $args
     */
    public function renderSelect(array $args)
    {
        $properties = [
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<select ' . $this->printFieldProperties($properties) . '>';
        foreach ($args['options'] as $key => $value) {
            echo '<option name="' . esc_attr($key) . '"' . selected($key, $args['value'], false) . '>' . esc_html($value) . '</option>';
        }
        echo '</select>';
    }


    /**
     * Render <textarea /> element.
     *
     * Note: method expects the value argument ($args['value']) to be an array
     * (of lines).
     *
     * @param array $args
     */
    public function renderTextArea(array $args)
    {
        // Field properties
        $properties = [
            'id'        => $args['label_for'],
            'name'      => $args['name'],
        ];

        echo '<textarea ' . $this->printFieldProperties($properties) . '>' . esc_html(implode(PHP_EOL, $args['value'])) . '</textarea>';

        if (isset($args['append'])) {
            echo '<br>' . esc_html($args['append']);
        }
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
	protected function printFieldProperties(array $properties)
    {
		$filtered = array_filter($properties,
			// Remove any false-like values (empty strings and false booleans) except for integers.
			function($value) { return is_int($value) || (is_string($value) && !empty($value)) || (is_bool($value) && $value); }
		);
		// Map keys and values together as key=value
		$mapped = array_map(
			function($key, $value) {
				// Boolean values are replaced with key name: checked => true ---> checked="checked"
				return sprintf('%s="%s"', $key, esc_attr(is_bool($value) ? $key : $value));
			},
			array_keys($filtered),
			array_values($filtered)
		);
		// Join all properties into single string
		return implode(' ', $mapped);
	}
}
