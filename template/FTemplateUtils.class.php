<?php
class FTemplateUtils {
	public static function checkbox ($label, $name, $repost = null, $value = 1, $id = null, $return = false) {
		$id = str_replace('_', '-', $name);
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
	public static function checked ($value, $check = null, $return = false) {
		if (
			($check === null && $value != '')
			|| ($check !== null && (string)$value === (string)$check)
		) {
			if ($return) return ' checked="checked"'; else echo ' checked="checked"';
		}
	}
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
	public static function selectOptions ($options, $selected = null, $return = false) {
		$option_list = '';
		$selected = (string)$selected;
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
		if ($return) return $option_list; else echo $option_list;
	}
}
