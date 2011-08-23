<?php
$date_range = $event->getStart()->format('d, H:i')
	. ' - '
	. $event->getEnd()->format('d, H:i');
?>
				<div class="event">
					<div class="title" style="background:#<?php echo substr(md5($date_range), 0, 6); ?>"><?php echo $date_range; ?></div>
				</div>
