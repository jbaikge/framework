<form method='<?php echo strtolower($form->get('method', 'post')); ?>' action='<?php echo $form->get('action', $_SERVER['REQUEST_URI']); ?>'>
	<fieldset>
		<legend></legend>
		<ol>
<?php
foreach($form->getFields() as $field) {
	echo "<li>\n" . FTemplate::fetch($field->getTemplate(), array('field' => &$field, 'form' => &$form)) . "</li>\n";
}
?>
		</ol>
		<div class="buttons">
			<input type="Submit" value="Submit" class="FSubmitButton">
		</div>
	</fieldset>
</form>
