<?php
/*!
 * Base calendar class to hold groups of events. Whether used directly or
 * extended, this class will automatically sort any FCalendarEvent object
 * inserted into it. This sorting helps later when it comes time to display
 * events to the user.
 * 
 * @date Sat Jul 30 18:30:06 EDT 2011
 * @author Jake Tews <jtews@okco.com>
 * @package Calendar
 */
class FCalendar extends SplMinHeap {
	/*!
	 * Compare two FCalendarEvents to determine what order they should be placed
	 * within the internal heap.
	 * 
	 * To decrease processing, events are compared in a two-stage process:
	 * @li Stage One: Compare start dates. If the difference is nonzero, return
	 * immediately.
	 * @li Stage Two: If the start difference is zero, return the difference of
	 * the end dates.
	 * 
	 * @see http://php.net/manual/en/splminheap.compare.php
	 * @param $event1 First FCalendarEvent to compare
	 * @param $event2 Second FCalendarEvent to compare
	 * @return Result of the comparison, positive integer if value1 is greater
	 * than value2, 0 if they are equal, negative integer otherwise.
	 */
	public function compare ($event1, $event2) {
		$diff = $this->dateCompare($event1->getStart(), $event2->getStart());
		if ($diff == 0) {
			$diff = $this->dateCompare($event1->getEnd(), $event2->getEnd());
		}
		return $diff;
	}
	/*!
	 * Insert a new FCalendarEvent into the heap. Event must be a subclass of
	 * FCalendarEvent, or an InvalidArgumentException is thrown.
	 * 
	 * @see http://www.php.net/manual/en/splheap.insert.php
	 * @throws InvalidArgumentException if argument is not an FCalendarEvent
	 * @param $event Must be of type FCalendarEvent or an 
	 * InvalidArgumentException will be thrown.
	 * @return void
	 */
	public function insert ($event) {
		// Wanted to hint the $event param to FCalendarEvent, but throws a
		// strict standards warning.
		if (!($event instanceof FCalendarEvent)) {
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . '() must be an instance of FCalendarEvent');
		}
		$valid = $event->isValid();
		if ($valid === true) {
			parent::insert($event);
		} else {
			$reasons[$valid & FCalendarEvent::INVALID_UID] = "Invalid UID";
			$reasons[$valid & FCalendarEvent::INVALID_TIMESTAMP] = "Invalid Timestamp";
			$reasons[$valid & FCalendarEvent::INVALID_START] = "Invalid Start Date";
			$reasons[$valid & FCalendarEvent::INVALID_END] = "Invalid End Date";
			unset($reasons[0]);
			$message = 'Adding new event failed due to the following reason(s): '
				. implode(', ', $reasons);
			throw new FCalendarEventInvalidException($message, $valid);
		}
	}
	/*!
	 * Internal comparison for DateTime objects
	 * 
	 * @param $date1 DateTime object
	 * @param $date2 DateTime object
	 * @return 1 if $date1 < $date2; -1 if $date1 > $date2; 0 if $date1 ==
	 * $date2
	 */
	private function dateCompare (DateTime $date1, DateTime $date2) {
		$diff = 0;
		if ($date1 > $date2) {
			$diff = -1;
		} else if ($date1 < $date2) {
			$diff = 1;
		}
		return $diff;
	}
}
