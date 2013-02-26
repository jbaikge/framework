<?php
/*!
 * Template utilities handy for development of common HTML constructs.
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Fri Mar  7 21:36:17 EST 2008
 * @version $Id$
 */
class FTemplateUtils {
	/*!
	 * Creates an input field with the type checkbox, surrounded by a label with
	 * optional text. The checked state of the checkbox is determined by 
	 * utilizing #checked() with the values for $repost and $value parameters or
	 * this method.
	 * 
	 * Simplest Implementation:
	 * @code
	 * FTemplateUtils::checkbox('My Checkbox', 'checkbox_name', $post['checkbox_name']);
	 * @endcode
	 * 
	 * Generated HTML when not selected (wrapped for legibility):
	 * @code
	 * <label for="checkbox-name" class="checkbox">
	 *     <input type="checkbox" id="checkbox-name" name="checkbox_name" value="1">
	 *     My Checkbox
	 * </label>
	 * @endcode
	 * 
	 * Generated HTML when selected, or @c checkbox_name = 1 (wrapped for
	 * legibility):
	 * @code
	 * <label for="checkbox-name" class="checkbox">
	 *     <input type="checkbox" id="checkbox-name" name="checkbox_name" value="1" checked="checked">
	 *     My Checkbox
	 * </label>
	 * @endcode
	 * 
	 * @param $label Text to display describing the action of the checkbox
	 * @param $name Name to associate data with when the form is posted
	 * @param $repost @b Optional. If this checkbox is part of a form, the
	 * repost value will cause this checkbox to be checked if it matches the 
	 * $value value. Default: @c null
	 * @param $value @b Optional. The value to send when this checkbox is
	 * checked. Default: 1
	 * @param $id @b Optional. ID to use for the label and input tags. If none
	 * is supplied, the ID is derived from the $name parameter by converting all
	 * underscores to dashes. Default: @c null
	 * @param $return @b Optional. If @c true, the generated HTML is returned as
	 * a string. If @c false, the generated HTML is echo'd directly to the
	 * screen.
	 * @return If the $return parameter is @c true, a properly formatted HTML 
	 * definition of a checkbox, wrapped in a label and the checked state 
	 * already defined based on the inputs above.
	 */
	public static function checkbox ($label, $name, $repost = null, $value = 1, $id = null, $return = false) {
		$id = ($id === null) ? str_replace('_', '-', $name) : $id;
		$html = sprintf(
			'<label for="%s" class="checkbox"><input type="checkbox" id="%s" name="%s" value="%s"%s> %s</label>',
			$id,
			$id,
			$name,
			$value,
			self::checked($repost, $value, true),
			htmlize($label)
		);
		if ($return) return $html; else echo $html;
	}
	/*!
	 * A convenience method for the common task of determining the checked state
	 * of an HTML checkbox or radio field. The value and the check value are
	 * both converted to strings and are absolutely checked (using === instead 
	 * of ==) to ensure they match.
	 * 
	 * @param $value Original value of "checkable" item.
	 * @param $check Optional. Value to check against value, typically the
	 * repost value. Default: @c null.
	 * @param $return @b Optional. If @c true, the generated HTML is returned as
	 * a string. If @c false, the generated HTML is echo'd directly to the
	 * screen.
	 * @return If the $return parameter is @c true, a properly formatted HTML 
	 * definition of a checked status of a checkbox or radio input tag.
	 */
	public static function checked ($value, $check = null, $return = false) {
		if (
			($check === null && $value != '')
			|| ($check !== null && (string)$value === (string)$check)
		) {
			if ($return) return ' checked="checked"'; else echo ' checked="checked"';
		}
	}
	/*!
	 * Creates an input field with the type radio, surrounded by a label with
	 * optional text. The checked state of the radio button is determined by 
	 * utilizing #checked() with the values for $repost and $value parameters or
	 * this method.
	 * 
	 * Simplest Implementation (Note the fourth parameter):
	 * @code
	 * FTemplateUtils::radio('My Radio Option 1', 'radio_name', $post['radio_name'], 1);
	 * FTemplateUtils::radio('My Radio Option 2', 'radio_name', $post['radio_name'], 2);
	 * FTemplateUtils::radio('My Radio Option 3', 'radio_name', $post['radio_name'], 3);
	 * @endcode
	 * 
	 * Generated HTML with none selected (wrapped for legibility):
	 * @code
	 * <label for="radio-name-1" class="radio">
	 *     <input type="radio" id="radio-name-1" name="radio_name" value="1">
	 *     My Radio Option 1
	 * </label>
	 * <label for="radio-name-2" class="radio">
	 *     <input type="radio" id="radio-name-2" name="radio_name" value="2">
	 *     My Radio Option 2
	 * </label>
	 * <label for="radio-name-3" class="radio">
	 *     <input type="radio" id="radio-name-3" name="radio_name" value="3">
	 *     My Radio Option 3
	 * </label>
	 * @endcode
	 * 
	 * Generated HTML when @c radio_name = 2 selected (wrapped for legibility):
	 * @code
	 * <label for="radio-name-1" class="radio">
	 *     <input type="radio" id="radio-name-1" name="radio_name" value="1">
	 *     My Radio Option 1
	 * </label>
	 * <label for="radio-name-2" class="radio">
	 *     <input type="radio" id="radio-name-2" name="radio_name" value="2" checked="checked">
	 *     My Radio Option 2
	 * </label>
	 * <label for="radio-name-3" class="radio">
	 *     <input type="radio" id="radio-name-3" name="radio_name" value="3">
	 *     My Radio Option 3
	 * </label>
	 * @endcode
	 * 
	 * @param $label Text to display describing the action of the radio button
	 * @param $name Name to associate data with when the form is posted
	 * @param $repost @b Optional. If this radio button is part of a form, the
	 * repost value will cause this radio button to be checked if it matches the 
	 * $value value. Default: @c null
	 * @param $value @b Optional. The value to send when this radio button is
	 * selected. Default: 1
	 * @param $id @b Optional. ID to use for the label and input tags. If none
	 * is supplied, the ID is derived from the $name parameter by converting all
	 * underscores to dashes. Default: @c null
	 * @param $return @b Optional. If @c true, the generated HTML is returned as
	 * a string. If @c false, the generated HTML is echo'd directly to the
	 * screen.
	 * @return If the $return parameter is @c true, a properly formatted HTML 
	 * definition of a radio button, wrapped in a label and the checked state 
	 * already defined based on the inputs above.
	 */
	public static function radio ($label, $name, $repost = null, $value = 1, $id = null, $return = false) {
		$id = str_replace(array('_', '[', ']'), array('-', '', ''), $name . '-' . $value);
		$html = sprintf(
			'<label for="%s" class="radio"><input type="radio" id="%s" name="%s" value="%s"%s> %s</label>',
			$id,
			$id,
			$name,
			$value,
			self::checked($repost, $value, true),
			htmlize($label)
		);
		if ($return) return $html; else echo $html;
	}
	/*!
	 * Generates all necessary option HTML tags to populate a select HTML tag.
	 * Additionally, this method automatically selects the correct one when a
	 * repost value is supplied.
	 * 
	 * Example array of options:
	 * @code
	 * $fruits = array(
	 *     '' => "Select a fruit",
	 *     'apple' => "Apple",
	 *     'banana' => "Banana",
	 *     'orange' => "Orange"
	 * );
	 * @endcode
	 * 
	 * Example implmentation inside a template:
	 * @code
	 * <select name="fruit" id="fruit"><?php
	 *     FTemplate::selectOptions($fruits, $post['fruit']);
	 * ?></select>
	 * @endcode
	 * 
	 * Generated HTML with no selection (indented for legibility):
	 * @code
	 * <select name="fruit" id="fruit">
	 *     <option value="">Select a fruit</option>
	 *     <option value="apple">Apple</option>
	 *     <option value="banana">Banana</option>
	 *     <option value="orange">Orange</option>
	 * </select>
	 * @endcode
	 * 
	 * Generated HTML with @c fruit = banana (indented for legibility):
	 * @code
	 * <select name="fruit" id="fruit">
	 *     <option value="">Select a fruit</option>
	 *     <option value="apple">Apple</option>
	 *     <option value="banana">Banana</option>
	 *     <option value="orange">Orange</option>
	 * </select>
	 * @endcode
	 */
	public static function selectOptions ($options, $selected = null, $return = false) {
		$option_list = '';
		foreach ($options as $key => $value) {
			if (is_array($selected)) {
				$selected_attribute = (array_search($key, $selected) !== false) ? ' selected="selected"' : '';
			} else {
				$selected_attribute = ($key == (string)$selected) ? ' selected="selected"' : '';
			}
			$option_list .= sprintf(
				'<option value="%s"%s>%s</option>',
				// Test for is_numeric() without the function call.
				// Saves time and memory when $key is an ID.
				((string)(float)$key === (string)$key) ? $key : htmlize($key),
				$selected_attribute,
				htmlize($value)
			);
		}
		if ($return) return $option_list; else echo $option_list;
	}
}
