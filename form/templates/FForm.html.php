<form method='<?php echo strtolower($form->get('method', 'post')); ?>' action='<?php echo $form->get('action', $_SERVER['REQUEST_URI']); ?>' id="Form<?php echo get_class($form->getInnerInstance()); ?>">
	<fieldset>
		<legend><?php e($form->get('legend', '')); ?></legend>
<?php
if ($form->get('error', false) != false) {
	echo "\t\t<p class=\"error\">" . htmlize($form->get('error')) . "</p>\n";
}
?>
		<ol>
<?php
foreach($form->getFields() as $field) {
	if (!$field->hidden) {
		echo "\t\t\t<li>" . FTemplate::fetch($field->getTemplate(), array('field' => &$field, 'form' => &$form)) . "</li>\n";
	}
}
?>
		</ol>
		<div class="hidden">
<?php
foreach ($form->getFields() as $field) {
	if ($field->hidden) {
		echo "\t\t\t" . FTemplate::fetch($field->getTemplate(), array('field' => &$field, 'form' => &$form)) . "\n";
	}
}
?>
		</div>
		<div class="buttons">
			<input type="Submit" value="Submit" class="FSubmitButton">
		</div>
	</fieldset>
</form>
