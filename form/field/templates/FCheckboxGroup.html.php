<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<?php 
	$incr = 0;
	$repost = $field->getValue();
	foreach ($field->get('options') as $cb_value => $cb_label) {
		FTemplateUtils::checkbox($cb_label, $field->getName().'['.$incr.']', $repost[$incr], $field->get('value', $cb_value), $cb_value);
		$incr++;
	}
?>