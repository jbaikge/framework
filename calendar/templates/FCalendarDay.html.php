<?php

$classname = 'day';
$date = $day->getDate();
$yesterday = clone($date);
$yesterday->sub(new DateInterval('P1D'));
$tomorrow = clone($date);
$tomorrow->add(new DateInterval('P1D'));
$month_start = $month->getStartDate();
$month_end = $month->getEndDate();
if ($date < $month_start || $date > $month_end) {
	$classname = 'outer-day';
}

if ($date == $month_start || $date == $month_end || $tomorrow == $month_start || $yesterday == $month_end) {
	$date_number = $date->format('M j');
} else {
	$date_number = $date->format('j');
}

?>
		<div class="<?php echo $classname; ?>">
			<div class="date"><?php echo $date_number; ?></div>
<?php

if (count($day)) {
	for ($i = 0; $i <= $day->getMaxOffset(); $i++) {
		if (isset($day[$i])) {
			echo FTemplate::fetchCached($month->getTemplateFor('event'), array(
				'month' => &$month,
				'week'  => &$week,
				'day'   => &$day,
				'event' => &$day[$i]
			));
		} else {
			echo FTemplate::fetchCached($month->getTemplateFor('blank'), array(
				'month' => &$month,
				'week'  => &$week,
				'day'   => &$day
			));
		}
	}
}

?>
		</div>
