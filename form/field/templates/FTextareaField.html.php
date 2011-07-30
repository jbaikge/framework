<?php echo FTemplate::fetchCached(dirname(__FILE__) . '/FLabelField.html.php', array('form' => &$form, 'field' => &$field)); ?>
<textarea name="<?php echo $field->getName(); ?>" id="<?php echo $field->getId(); ?>" rows="<?php echo $field->getRows(); ?>" cols="<?php echo $field->getCols(); ?>"><?php t($field->getValue()); ?></textarea>
