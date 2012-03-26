<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<?php 
	foreach ($field->get('options') as $value => $label) {
		FTemplateUtils::checkbox(
			/* label  */ $label,
			/* name   */ $field->getName().'['.$value.']',
			/* repost */ in_array($value, $field->getValue()) ? $value : null,
			/* value  */ $value
		);
	}
?>
