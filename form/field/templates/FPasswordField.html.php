<?php
echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field));

$attributes = '';
if ($field->get('placeholder')) {
	$attributes .= ' placeholder="' . htmlize($field->get('placeholder')) . '"';
}
if ($field->get('tabindex')) {
	$attributes .= sprintf(' tabindex="%d"', $field->get('tabindex'));
}
?>
<input type="password" value="" name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>" class="<?php echo get_class($field); ?>"<?php echo $attributes; ?>>
