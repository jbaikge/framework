<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<input type="password" value="" name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>" class="<?php echo get_class($field); ?>">
