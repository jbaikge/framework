<?php
class FWebrootFilter implements FTemplateRenderFilter {
	public function filter ($content) {
		if (WEBROOT) {
			$attributes = array('href', 'src', 'action');
			$searches = array();
			$replacements = array();
			foreach ($attributes as $attribute) {
				$searches[] = $attribute . '="//';
				$searches[] = $attribute . "='//";
				$searches[] = $attribute . '="' . WEBROOT . '/';
				$searches[] = $attribute . "='" . WEBROOT . '/';
				$searches[] = $attribute . '="/';
				$searches[] = $attribute . "='/";
				$searches[] = $attribute . '=" //';
				$searches[] = $attribute . "=' //";
				$replacements[] = $attribute . '=" //';
				$replacements[] = $attribute . "=' //";
				$replacements[] = $attribute . '="/';
				$replacements[] = $attribute . "='/";
				$replacements[] = $attribute . '="' . WEBROOT . '/';
				$replacements[] = $attribute . "='" . WEBROOT . '/';
				$replacements[] = $attribute . '="//';
				$replacements[] = $attribute . "='//";
			}
			return str_replace($searches, $replacements, $content);
		} else {
			return $content;
		}
	}
}
