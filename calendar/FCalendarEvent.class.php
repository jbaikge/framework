<?php
/*!
 * Single event which implements RFC 5545 as closely as possible.
 * 
 * A typical build of an event with all required attributes, where the UID and
 * timestamp are derived from the date created:
 * @code
 * $event = FCalendarEvent::newInstance()
 *     ->setCreated('Aug 1, 2011 13:45:56')
 *     ->setStart('Aug 4, 2011 14:30')
 *     ->setEnd('Aug 4, 2011 15:00');
 * @endcode
 * 
 * @date Sat Jul 30 18:30:06 EDT 2011
 * @author Jake Tews <jtews@okco.com>
 * @package Calendar
 */
class FCalendarEvent {
	const INVALID_UID = 0x01;
	const INVALID_TIMESTAMP = 0x02;
	const INVALID_START = 0x04;
	const INVALID_END = 0x08;
	// 0x10, 0x20, 0x40, 0x80, 0x100, 0x200...
	//private $attachment; ///< 3.8.1.1. Attachment
	//private $attendees; ///< 3.8.4.1. Attendee
	//private $categories; ///< 3.8.1.2. Categories
	//private $classification; ///< 3.8.1.3. Classification
	//private $comment; ///< 3.8.1.4. Comment
	//private $contacts; ///< 3.8.4.2. Contact
	private $created; ///< 3.8.7.1. Date-Time Created
	private $description; ///< 3.8.1.5. Description
	private $duration; ///< 3.8.2.5. Duration
	private $end; ///< 3.8.2.2. Date-Time End
	//private $exceptionDateTimes; ///< 3.8.5.1. Exception Date-Times
	private $extra = array(); ///< Sometimes you just need a little extra
	//private $lastModified; ///< 3.8.7.3. Last Modified
	private $location; ///< 3.8.1.7. Location
	//private $method; ///< 3.7.2. Method
	//private $organizers; ///< 3.8.4.3. Organizer
	//private $priority; ///< 3.8.1.9. Priority
	//private $productIdentifier; ///< 3.7.3. Product Identifier
	//private $recurrences; ///< 3.8.5.2. Recurrence Date-Times
	//private $recurranceId; ///< 3.8.4.4. Recurrence ID
	//private $recurrenceRule; ///< 3.8.5.3. Recurrence Rule
	//private $resources; ///< 3.8.1.10. Resources
	//private $sequence; ///< 3.8.7.4. Sequence Number
	private $start; ///< 3.8.2.4. Date-Time Start
	private $status; ///< 3.8.1.11. Status
	private $summary; ///< 3.8.1.12. Summary
	private $timestamp; ///< 3.8.7.2. Date-Time Stamp
	private $timezone;
	//private $timezoneId; ///< 3.8.3.1. Time Zone Identifier
	//private $timezoneName; ///< 3.8.3.2. Time Zone Name
	//private $timezoneOffsetFrom; ///< 3.8.3.3. Time Zone Offset From
	//private $timezoneOffsetTo; ///< 3.8.3.4. Time Zone Offset To
	private $uid; ///< 3.8.4.7. Unique Identifier
	private $url; ///< 3.8.4.6. Uniform Resource Locater
	//private $version; ///< 3.7.4. Version
	/*!
	 * Builds a new instance of an FCalendarEvent to make chaining easier.
	 * 
	 * Example Implementation:
	 * @code
	 * $event = FCalendarEvent::newInstance()
	 *     ->setCreated($object->added)
	 *     ->setStart($object->date_start)
	 *     ->setEnd($object->date_end);
	 * @endcode
	 * 
	 * @return New instance of FCalendarEvent.
	 */
	public static function newInstance () {
		return new FCalendarEvent();
	}
	/*!
	 * Fetches a DateTime object representing this event's created date.
	 * 
	 * @return DateTime object
	 */
	public function getCreated () {
		return $this->created;
	}
	/*!
	 * Fetches the description for this event.
	 * 
	 * @return String representing the description
	 */
	public function getDescription () {
		return $this->description;
	}
	/*!
	 * Fetches a DateInterval object representing the duration of the event.
	 * 
	 * @return DateInterval object
	 */
	public function getDuration () {
		return $this->duration;
	}
	/*!
	 * Fetches a DateTime object representing this event's end date.
	 * 
	 * @return DateTime object
	 */
	public function getEnd () {
		return $this->end;
	}
	/*!
	 * Fetches any extra data items placed in the event using setExtra().
	 * 
	 * @param $key Key to retrieve data for
	 * @return Data corresponding to the key
	 */
	public function getExtra ($key) {
		return isset($this->extra[$key]) ? $this->extra[$key] : null;
	}
	/*!
	 * Fetches the location for this event.
	 * 
	 * @return String representing the location
	 */
	public function getLocation () {
		return $this->location;
	}
	/*!
	 * Fetches a DateTime object representing this event's start date.
	 * 
	 * @return DateTime object
	 */
	public function getStart () {
		return $this->start;
	}
	/*!
	 * Fetches the summary for this event.
	 * 
	 * @return String representing the summary
	 */
	public function getSummary () {
		return $this->summary;
	}
	/*!
	 * Fetches the timzone for this event.
	 * 
	 * @return DateTimeZone object.
	 */
	public function getTimezone () {
		if ($this->timezone) {
			return $this->timezone;
		} else {
			return new DateTimeZone(ini_get('date.timezone'));
		}
	}
	/*!
	 * Fetches the title for this event. Alias for getSummary().
	 * 
	 * @see FCalendarEvent::getSummary()
	 * @return String representing the title
	 */
	public function getTitle () {
		return $this->getSummary();
	}
	/*!
	 * 
	 */
	public function getUID () {
		return $this->uid;
	}
	/*!
	 * Fetches a DateTime object representing this event's updated date.
	 * 
	 * @return DateTime object
	 */
	public function getUpdated () {
		return $this->lastModified;
	}
	/*!
	 * Fetches the URL for this event.
	 * 
	 * @return String representing the URL
	 */
	public function getURL () {
		return $this->url;
	}
	/*!
	 * Verifies that the required elements, UID and start time, as well as
	 * duration OR end time, are set.
	 *
	 * @return @c true if required elements are set. If not, an integer is
	 * returned with error codes OR'd together.
	 */
	public function isValid () {
		$checks = array(
			FCalendarEvent::INVALID_UID => isset($this->uid),
			FCalendarEvent::INVALID_TIMESTAMP => isset($this->timestamp),
			FCalendarEvent::INVALID_START => isset($this->start),
			FCalendarEvent::INVALID_END => isset($this->duration) || isset($this->end),
		);
		$errors = 0;
		foreach ($checks as $error => $check) {
			if (!$check) {
				$errors |= $error;
			}
		}
		return ($errors) ? $errors : true;
	}
	/*!
	 * Sets the created date of the event. A call to this method will also set
	 * the timestamp of the event if it has not already been set. Additionally,
	 * if the UID is not set, this method will automatically generate a UID.
	 * 
	 * The format for the timestamp may be any one of:
	 * @li Free-form string
	 * @li UNIX timestamp
	 * @li DateTime object
	 * 
	 * Examples of a Free-Form String:
	 * @li August 1, 2011
	 * @li August 1, 2011 4:30pm
	 * @li tomorrow
	 * 
	 * Examples of a UNIX Timestamp:
	 * @li 1312171200
	 * @li 1312230600
	 * @li 1312257600
	 * 
	 * Examples of a DateTime Object:
	 * @li new DateTime('Aug 1, 2011')
	 * @li new DateTime('Aug 1, 2011 4:30pm')
	 * @li new DateTime('tomorrow')
	 * 
	 * @see http://php.net/manual/en/class.datetime.php
	 * @param $timestamp A representation of date and time..
	 * @return Reference back to this instance.
	 */
	public function &setCreated ($timestamp) {
		$this->created = $this->valueToDateTime($timestamp);
		if (!$this->timestamp) {
			$this->setTimestamp($this->created);
		}
		return $this;
	}
	/*!
	 * Sets the description of the event. Depending on where or how the event
	 * may be utilized, the description may consist of HTML 
	 * 
	 * @param $description Event description
	 * @return Reference back to this instance.
	 */
	public function &setDescription ($description) {
		$this->description = $description;
		return $this;
	}
	/*!
	 * Sets the duration of the event. This method may be used in lieu of
	 * setEnd() to set the end date of the event. If both methods, setDuration()
	 * and setEnd() are called, the last one called will have precedence.
	 * 
	 * The format expected for the single argument may be one of the following:
	 * @li Free-form string
	 * @li ICAL compatible formatted string (see examples)
	 * @li DateInterval object
	 * 
	 * Examples of free-form strings:
	 * @li 15 minutes
	 * @li 2 days
	 * @li 2 hours 30 minutes
	 * @li 1 month 3 weeks 4 hours 12 minutes
	 * 
	 * Examples of ICAL compatible formatted strings:
	 * @li P15M
	 * @li P2D
	 * @li P2H30M
	 * @li P1M3WT4H12M // Note the T
	 * 
	 * Examples of DateInterval Objects:
	 * @li new DateInterval('P15M')
	 * @li new DateInterval('2 days')
	 * @li new DateInterval('P2H30M')
	 * @li new DateInterval('1 month 3 weeks 4 hours 12 minutes')
	 * 
	 * @see http://php.net/manual/en/class.dateinterval.php
	 * @param $duration A representation of duration
	 * @return Reference back to this instance.
	 */
	public function &setDuration ($duration) {
		if ($duration instanceof DateInterval) {
			$this->duration = $duration;
		} else if ($duration[0] == 'P') {
			$this->duration = new DateInterval($duration);
		} else {
			$this->duration = DateInterval::createFromDateString($duration);
		}
		unset($this->end);
		$this->fixDurationAndEnd();
		return $this;
	}
	/*!
	 * Sets the end date of this event. Similarly to setDuration(), this method
	 * may be used in lieu of setDuration() to set the duration of the event. If
	 * both methods, setDuration() and setEnd(), are called, the last one called
	 * will have precedence.
	 * 
	 * @see FCalendarEvent::setCreated()
	 * @param $end A representation of date and time.
	 * @return Reference back to this instance.
	 */
	public function &setEnd ($end) {
		$this->end = $this->valueToDateTime($end);
		unset($this->duration);
		$this->fixDurationAndEnd();
		return $this;
	}
	/*!
	 * Sets extra information describing this event. The information may be
	 * necessary for templates or other programming logic, but does not fall
	 * under the available existing methods or comply with RFC 5545.
	 * 
	 * setExtra() acts as a storage hash where they key must be a proper array
	 * key type, such as an integer or string. The value may be any type, but
	 * keep in mind that a calendar object, such as FCalendarDay or
	 * FCalendarMonth may need to store many of these instances. If the values
	 * set as extras are too "heavy", memory exhaustion errors may occur.
	 * 
	 * @param $key Key to store the supplied value in
	 * @param $value Value to set to the supplied key
	 * @return Reference back to this instance.
	 */
	public function &setExtra ($key, $value) {
		$this->extra[$key] = $value;
		return $this;
	}
	/*!
	 * Location where this event is to take place. Valid values may include:
	 * @li Conference room
	 * @li Bob's office
	 * @li 1600 Pennsylvania Ave, Washington, DC
	 * 
	 * @param $location Location where event is to take place
	 * @return Reference back to this instance.
	 */
	public function &setLocation ($location) {
		$this->location = $location;
		return $this;
	}
	/*!
	 * Sets the start date and time of this event. If no time is specified for 
	 * the @c $start argument, the event will begin at midnight.
	 * 
	 * @see FCalendarEvent::setCreated()
	 * @param $start A representation of date and time
	 * @return Reference back to this instance.
	 */
	public function &setStart ($start) {
		$this->start = $this->valueToDateTime($start);
		$this->fixDurationAndEnd();
		return $this;
	}
	/*!
	 * Sets the timestamp for this event. Note that this timestamp is @b not the
	 * same as the created, start, or end timestamps. This timestamp conforms to
	 * RFC 5545 3.8.7.2 and is required for representing an event, according to
	 * the Internet Calendaring and Scheduling standard.
	 * 
	 * The value supplied for this method typically represents the exact time
	 * the event was created, not necessarily when the event starts. This value
	 * also will not always be the same as the created timestamp, but that is
	 * another topic of discussion handled by the RFC and not this
	 * documentation.
	 * 
	 * As with setCreated(), setStart(), and setEnd(), the @c $timestamp
	 * parameter will accept the same types of arguments.
	 * 
	 * @see FCalendarEvent::setCreated()
	 * @param $timestamp Timestamp of event creation
	 * @return Reference back to this instance.
	 */
	public function &setTimestamp ($timestamp) {
		$this->timestamp = $this->valueToDateTime($timestamp);
		if (!$this->uid) {
			$this->setUID($this->timestamp->format(DateTime::ISO8601));
		}
		return $this;
	}
	/*!
	 * Sets the timezone of this event. Recommended to call this method before
	 * calling other methods concerning dates and times, but it will
	 * retroactively apply changes if called later.
	 * 
	 * @param $timezone Valid timzone (America/New_York)
	 * @return Reference back to this instance
	 */
	public function &setTimezone ($timezone) {
		if ($timezone instanceof DateTimeZone) {
			$this->timezone = $timezone;
		} else {
			$this->timezone = new DateTimeZone($timezone);
		}
		$datetime_members = array(
			$this->created,
			$this->duration,
			$this->start,
			$this->timestamp
		);
		foreach ($datetime_members as &$member) {
			if ($member) {
				$member->setTimezone($this->timezone);
			}
		}
		return $this;
	}
	/*!
	 * Sets the title of the event. This method differs slightly in the
	 * definition of an event as defined by RFC 5545 where the field "summary"
	 * is used instead. According to the documentation, the "summary" field is
	 * actually representative of the title of the event. Thusly, this method
	 * reflects the latter fact.
	 * 
	 * @param $title Title of the event.
	 * @return Reference back to this instance.
	 */
	public function &setTitle ($title) {
		$this->summary = $title;
		return $this;
	}
	/*!
	 * Sets the Unique Identifier. The UID must be unique across all events
	 * created from a single entity, or host. When specifying a UID, choose one
	 * that will always be the same when referring to the same event. An MD5
	 * hash of an event's primary key usually works great.
	 * 
	 * If setCreated() or setTimestamp() are called and this method is never
	 * called, the UID is automatically set to the value of setTimestamp() with
	 * the host appended. For example: 2011-08-01T09:34:56Z@example.com
	 * 
	 * Automatic host suffixing is a part of the specification and is
	 * recommended to help group events from a single origin. Events generated
	 * from a web interface will utilize the @c SERVER_NAME value, while those
	 * generated from the command line will utilize the hostname of the machine.
	 * 
	 * @param $uid Unique Identifier
	 * @param $auto_append_server @b Optional. Automatically append server
	 * information to the @c $uid argument.
	 * @return Reference back to this instance.
	 */
	public function &setUID ($uid, $auto_append_server = true) {
		$this->uid = $uid;
		if ($auto_append_server) {
			if (isset($_SERVER['SERVER_NAME'])) {
				$host = $_SERVER['SERVER_NAME'];
			} else {
				$host = gethostname();
			}
			$this->uid .= '@' . $host;
		}
		return $this;
	}
	/*!
	 * Sets the date the event's details were last updated. This date is similar
	 * to the date_updated field in a common database schema. This value is 
	 * especially important if this event is to be used for an ICS file later as
	 * calendar clients will respect the updated information more readily.
	 * 
	 * As with the other date-based methods, the date value for the @c $updated
	 * parameter may be any acceptable date format described in 
	 * FCalendarEvent::setCreated().
	 * 
	 * @see FCalendarEvent::setCreated()
	 * @param $updated A representation of a date and time
	 * @return Reference back to this instance.
	 */
	public function &setUpdated ($updated) {
		$this->lastModified = $this->valueToDateTime($updated);
		$this->timestamp = clone($this->lastModified);
		return $this;
	}
	/*!
	 * Sets a URL for this event. If this event is transformed into an .ics file
	 * for a calendaring client, it is up to the client to handle the value for
	 * the URL. For a web-rendering, this value becomes very useful.
	 * 
	 * @note A fully qualified URL is required. This includes the http:// bit.
	 * 
	 * @param $url
	 * @return Reference back to this instance.
	 */
	public function &setURL ($url) {
		if (filter_var($url, FILTER_VALIDATE_URL)) {
			$this->url = $url;
		} else {
			trigger_error('Invalid URL: ' . $url, E_USER_WARNING);
		}
		return $this;
	}
	private function fixDurationAndEnd () {
		if ($this->start) {
			if (isset($this->end) && !isset($this->duration)) {
				$this->duration = $this->start->diff($this->end, true);
			} else if (isset($this->duration) && !isset($this->end)) {
				$this->end = clone($this->start);
				$this->end->add($this->duration);
			}
		}
	}
	private function valueToDateTime ($value) {
		if ($value instanceof DateTime) {
			$date_time = $value;
		} else if (is_numeric($value)) {
			$date_time = new DateTime(null);
			$date_time->setTimestamp($value);
		} else {
			$date_time = new DateTime($value);
		}
		if ($this->timezone) {
			$date_time->setTimezone($this->timezone);
		}
		return $date_time;
	}
}

/*!
 * @date Sat Jul 30 18:30:06 EDT 2011
 * @author Jake Tews <jtews@okco.com>
 * @package Calendar
 */
class FCalendarEventInvalidException extends Exception {}
