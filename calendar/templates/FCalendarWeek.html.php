	<div class="week">
<?php

foreach ($week as $day) {
	echo FTemplate::fetchCached($month->getTemplateFor('day'), array(
		'month' => &$month,
		'week'  => &$week,
		'day'   => &$day
	));
} // foreach week days

?>
	</div>
