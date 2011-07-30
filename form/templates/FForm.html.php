<form method='<?php echo strtolower($form->get('method', 'post')); ?>' action='<?php echo $form->get('action', $_SERVER['REQUEST_URI']); ?>'>
	<fieldset>
		<legend></legend>
		<ol>
<?php
foreach($form->getFields() as $field) {
	echo "<li>" . FTemplate::fetch($field->getTemplate(), array('field' => &$field, 'form' => &$form)) . "</li>";
}
?>
		</ol>
		<input type="Submit" value="Submit">
	</fieldset>
</form>
