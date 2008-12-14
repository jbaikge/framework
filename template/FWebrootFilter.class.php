<?php
class FWebrootFilter implements FTemplateRenderFilter {
	public function filter ($content) {
		if (WEBROOT) {
			$attributes = array('href', 'src', 'action');
			$searches = array();
			$replacements = array();
			foreach ($attributes as $attribute) {
				$searches[] = $attribute . '="/';
				$searches[] = $attribute . "='/";
				$replacements[] = $attribute . '="' . WEBROOT . '/';
				$replacements[] = $attribute . "='" . WEBROOT . '/';
			}
			return str_replace($searches, $replacements, $content);
		} else {
			return $content;
		}
	}
}
