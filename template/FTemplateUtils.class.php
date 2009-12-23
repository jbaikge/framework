<?php
class FTemplateUtils {
	public static function selectOptions ($options, $selected = null, $return = false) {
		$option_list = '';
		foreach ($options as $key => $value) {
			$option_list .= sprintf('<option value="%s"', htmlize($key));
			if ($selected == $key) {
				$option_list .= ' selected="selected"';
			}
			$option_list .= sprintf('>%s</option>', htmlize($value));
		}
		if ($return) {
			return $option_list;
		} else {
			echo $option_list;
		}
	}
}
