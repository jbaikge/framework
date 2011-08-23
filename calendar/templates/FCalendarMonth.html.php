<?php /* $Id$ */ ?>
<div class="calendar">
	<div class="weekday-titles">
<?php

foreach ($month->getWeekdayNames() as $class_suffix => $name) {

?>
		<div class="weekday-<?php echo $class_suffix; ?>"><?php echo $name; ?></div>
<?php

} // foreach weekday names

?>
	</div>
<?php

foreach ($month->getCalendarWeeks() as $week) {
	echo FTemplate::fetchCached($month->getTemplateFor('week'), array(
		'month' => &$month,
		'week'  => &$week
	));
} // foreach calendar weeks

?>
</div>
