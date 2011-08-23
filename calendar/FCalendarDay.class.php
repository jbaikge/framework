<?php
/*!
 * Representation of a single calendar day.
 * 
 * The underlying datastructure for an instance of this class provides the
 * ability to set FCalendarEvent instances into "slots" where blank slots may
 * exist between occupied slots. These slots are representative of multi-day
 * events spanning multiple slots across different days consider the diagram
 * below:
 * 
 * @code
 * +-------+-------+-------+
 * | Aug 1 | Aug 2 | Aug 3 |
 * +-------+-------+-------+
 * | ***** | ***** |       |
 * |       | ***** | ***** |
 * +-------+-------+-------+
 * | SLT 1 | SLT 2 | SLT 2 |
 * | EVT 1 | EVT 2 | EVT 1 |
 * +-------+-------+-------+
 * @endcode
 * 
 * Two events are spanning multiple days, one from Aug 1 to Aug 2, and one from
 * Aug 2 to Aug 3.
 * 
 * On Aug 1, the first event starts and occupies the first slot. This event is
 * added with the offset set to FCalendarDay::AUTO_POSITION.
 * 
 * On Aug 2, the first event is forced into slot 0 since that offset is returned
 * when calling addEvent(). The second event is added with the offset
 * FCalendarEvent::AUTO_POSITION to sit in slot 1.
 * 
 * On Aug 3, the first event does not exist anymore, but the second event still
 * belongs in slot 1. Offset 1 is passed into addEvent() and the blank slot is
 * automatically produced. Slot 0 is now open for a new event passed in,
 * destined for the offset FCalendarDay::AUTO_POSITION.
 * 
 * @date Thu Aug  4 14:29:03 EDT 2011
 * @author Jake Tews <jtews@okco.com>
 * @package Calendar
 */
class FCalendarDay implements ArrayAccess, Countable, IteratorAggregate {
	const AUTO_POSITION = -1;
	private $count = 0;
	private $date;
	private $events;
	private $maxOffset = -1;
	public function __construct (DateTime $date) {
		$this->date = $date;
		$this->clear();
	}
	public function addEvent (FCalendarEvent &$event, $offset = FCalendarDay::AUTO_POSITION) {
		// Ensure event is valid
		$event_range = range(
			$event->getStart()->format('Ymd'),
			$event->getEnd()->format('Ymd')
		);
		if (!in_array($this->date->format('Ymd'), $event_range)) {
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . '() does not fall on this date (' . $this->date->format('Y-m-d') . ')');
		}
		if ($offset == FCalendarDay::AUTO_POSITION) {
			// Find the lowest offset 
			for ($i = 0; $i <= $this->maxOffset; $i++) {
				if (!isset($this->events[$i])) {
					$offset = $i;
					break;
				}
			}
			// If we didn't find an offset lower than maxOffset, append to the
			// end.
			if ($offset == FCalendarDay::AUTO_POSITION) {
				$offset = $this->maxOffset + 1;
			}
		}
		$this->maxOffset = max($offset, $this->maxOffset);
		if (!isset($this->events[$this->maxOffset])) {
			$this->events->setSize($this->maxOffset + 1);
		}
		++$this->count;
		$this->events[$offset] = $event;
		return $offset;
	}
	public function clear () {
		$this->events = new SplFixedArray();
		$this->count = 0;
		$this->maxOffset = -1;
	}
	public function count () {
		return count($this->events);
	}
	public function getDate () {
		return $this->date;
	}
	public function getIterator () {
		return $this->events;
	}
	public function getMaxOffset () {
		return $this->maxOffset;
	}
	public function getNumEvents () {
		return $this->count;
	}
	public function offsetExists ($offset) {
		return isset($this->events[$offset]);
	}
	public function offsetGet ($offset) {
		return $this->events[$offset];
	}
	public function offsetSet ($offset, $value) {
		$this->addEvent($value, $offset);
	}
	public function offsetUnset ($offset) {
		if (isset($this->events[$offset])) {
			--$this->count;
			unset($this->events[$offset]);
			while (!is_object($this->events[$this->maxOffset])) {
				$this->events->setSize($this->maxOffset);
				--$this->maxOffset;
			}
		}
	}
}
