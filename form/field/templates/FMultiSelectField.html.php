<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<select name="<?php echo $field->getName(); ?>[]" id="<?php echo $field->getId(); ?>" class="<?php echo get_class($field); ?>" size="<?php echo $field->get('size', 5); ?>" multiple>
	<?php FTemplateUtils::selectOptions($field->get('options', array()), $field->getValue()); ?>
</select>
