<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<?php 
	foreach ($field->get('options') as $rb_value => $rb_label) {
		FTemplateUtils::radio($rb_label, $field->getName(), $field->getValue(), $field->get('value', $rb_value), $rb_value);
	}
?>