<?php
echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field));

$attributes = '';
if ($field->get('size')) { 
	$attributes .= ' size="' . $field->get('size') . '"';
}
?>
<input type="text" value="<?php e($field->getValue()); ?>" name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>" class="<?php echo get_class($field); ?>"<?php echo $attributes; ?>>
