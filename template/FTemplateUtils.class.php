<?php
class FTemplateUtils {
	public static function selectOptions ($options, $selected = null, $return = false) {
		$option_list = '';
		foreach ($options as $key => $value) {
			$selected_attribute = ($key == $selected) ? ' selected="selected"' : '';
			$option_list .= sprintf(
				'<option value="%s"%s>%s</option>',
				// Test for is_numeric() without the function call.
				// Saves time and memory when $key is an ID.
				((string)(float)$key === (string)$key) ? $key : htmlize($key),
				$selected_attribute,
				htmlize($value)
			);
		}
		if ($return) {
			return $option_list;
		} else {
			echo $option_list;
		}
	}
}
