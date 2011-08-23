<?php
/*!
 * Handles all events in a single month.
 * 
 * @date Thu Aug  4 07:01:47 EDT 2011
 * @author Jake Tews <jtews@okco.com>
 * @package Calendar
 */

class FCalendarMonth extends FCalendar {
	private $calendarDays;
	private $calendarWeeks;
	private $days;
	private $dateEnd;
	private $dateStart;
	private $numDays;
	private $tainted = true;
	private $templates = array();
	private $weekdayFormat = 'l'; ///< Full weekday name Sunday - Saturday
	private $weeks;
	private $weekStart = 0; ///< Week start, 0 for Sunday, 6 for Saturday
	public function __construct ($month, $year, $format = "M Y") {
		$modified_format = 'd '.$format.' H:i:s';
		$modified_date = '01 '.$month.' '.$year . ' 00:00:00';
		$this->setStartEnd($modified_date, $modified_format);
		$this->setCalendarWeeks();
		$this->setDefaultTemplates();
	}
	public function clearEvents () {
		foreach ($this->days as &$day) {
			$day->clear();
		}
	}
	public function &getCalendarDays () {
		$this->setCalendarEvents();
		return $this->calendarDays;
	}
	public function getCalendarEnd () {
		$diff = abs((6 - $this->dateEnd->format('w')) + $this->weekStart);
		// So we don't have a week appended to the end
		if ($diff >= 7) {
			$diff -= 7;
		}
		$calendar_end = clone($this->dateEnd);
		if ($diff > 0) {
			$calendar_end->add(new DateInterval('P' . $diff . 'D'));
		}
		return $calendar_end;
	}
	public function getCalendarStart () {
		$diff = $this->dateStart->format('w') - $this->weekStart;
		$calendar_start = clone($this->dateStart);
		if ($diff > 0) {
			$calendar_start->sub(new DateInterval('P' . $diff . 'D'));
		} else if ($diff < 0) {
			$calendar_start->sub(new DateInterval('P' . (7 - abs($diff)) . 'D'));
		}
		return $calendar_start;
	}
	public function &getCalendarWeeks () {
		$this->setCalendarEvents();
		return $this->calendarWeeks;
	}
	public function getEndDate () {
		return $this->dateEnd;
	}
	/*!
	 * Returns the number of days in this month.
	 * 
	 * @return Number of days in this month.
	 */
	public function getNumDays () {
		return $this->numDays;
	}
	public function getStartDate () {
		return $this->dateStart;
	}
	public function getTemplate () {
		return $this->getTemplateFor('month');
	}
	public function getTemplateFor ($type) {
		return $this->templates[$type];
	}
	public function getWeekdayNames () {
		static $weekdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri');
		$weekdayNames = array();
		$period = new DatePeriod(
			new DateTime($weekdays[$this->weekStart]),
			new DateInterval("P1D"),
			6
		);
		foreach ($period as $date) {
			$weekdayNames[strtolower($date->format('l'))] = $date->format($this->weekdayFormat);
		};
		return $weekdayNames;
	}
	public function insert ($event) {
		if (!$this->tainted) {
			$this->clearEvents();
		}
		$this->tainted = true;
		parent::insert($event);
	}
	public function &setTemplateFor ($type, $template) {
		$this->templates[$type] = $template;
		return $this;
	}
	public function &setWeekdayNameFormat ($format) {
		$this->weekdayFormat = $format;
		return $this;
	}
	public function &setWeekStart ($start) {
		if ($start >= 0 && $start <= 6) {
			$this->weekStart = $start;
			$this->setCalendarWeeks();
		}
		return $this;
	}
	private function setCalendarDays () {
		$this->days = array();
		$this->calendarDays = array();
		$period = new DatePeriod($this->getCalendarStart(), new DateInterval('P1D'), $this->getCalendarEnd()->add(new DateInterval('P1D')));
		foreach ($period as $date) {
			$key = $date->format('Ymd');
			$this->days[$key] = new FCalendarDay($date);
			$this->calendarDays[] =& $this->days[$key];
		}
	}
	private function setCalendarEvents () {
		if ($this->tainted) {
			foreach ($this as $event) {
				$offset = FCalendarDay::AUTO_POSITION;
				foreach (range($event->getStart()->format('Ymd'), $event->getEnd()->format('Ymd')) as $key) {
					if (isset($this->days[$key])) {
						$offset = $this->days[$key]->addEvent($event, $offset);
					}
				}
			}
			$this->tainted = false;
		}
	}
	private function setCalendarWeeks () {
		if (!$this->calendarDays) {
			$this->setCalendarDays();
		}
		foreach ($this->calendarDays as $index => &$day) {
			$this->calendarWeeks[(int)($index / 7)][] = $day;
		}
	}
	private function setDefaultTemplates () {
		$this->templates = array(
			'month' => $_ENV['config']['templates.calendar.dir'] . DS . 'FCalendarMonth.html.php',
			'week'  => $_ENV['config']['templates.calendar.dir'] . DS . 'FCalendarWeek.html.php',
			'day'   => $_ENV['config']['templates.calendar.dir'] . DS . 'FCalendarDay.html.php',
			'event' => $_ENV['config']['templates.calendar.dir'] . DS . 'FCalendarEvent.html.php',
			'blank' => $_ENV['config']['templates.calendar.dir'] . DS . 'FCalendarBlank.html.php'
		);
	}
	private function setStartEnd ($date, $format) {
		$this->dateStart = DateTime::createFromFormat($format, $date);
		// Determine the end of the month to get the number of days:
		$this->dateEnd = clone($this->dateStart);
		$this->dateEnd->modify('+1 month -1 day');
		$this->numDays = (int)$this->dateStart->diff($this->dateEnd)->format('%a') + 1;
	}
}
