<label for="<?php echo $field->getId(); ?>"><?php
	e($field->getLabel());
	if ($sub_label = $field->getSubLabel()) {
		echo ' <small>(', htmlize($sub_label), ')</small>';
	}
	if ($error = $field->getError()) {
		echo ' <span class="error">', htmlize($error), '</span>';
	}
?></label>
