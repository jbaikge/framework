<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<select name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>">
	<?php FTemplateUtils::selectOptions($field->get('options', array()), $field->getValue()); ?>
</select>