<?php
echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field));

$attributes = '';
if ($field->get('placeholder')) {
	$attributes .= ' placeholder="' . htmlize($field->get('placeholder')) . '"';
}
?>
<textarea name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>" rows="<?php echo $field->getRows(); ?>" cols="<?php echo $field->getCols(); ?>" class="<?php echo get_class($field); ?>"<?php echo $attributes; ?>><?php t($field->getValue()); ?></textarea>
