<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<?php
$attributes = '';
if ($field->get('size')) { 
	$attributes .= ' size="' . $field->get('size') . '"';
}
?>
<input type="text" value="<?php e($field->getValue()); ?>" name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>"<?php echo $attributes; ?>>
