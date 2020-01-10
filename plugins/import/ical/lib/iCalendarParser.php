<?php

/*
 * EXtends the iCal Class
 */

require('ics-parser/class.iCalReader.php');

class iCalendar extends ICal {

	// override this as we may have a different value
	public function iCalDateToUnixTimestamp($icalDate) {
		if (strpos($icalDate, 'T') !== false) {	// then we have a good date
			return parent::iCalDateToUnixTimestamp($icalDate);
		}else{
			if (strlen($icalDate) == 8) {			// aleady set
				return parent::iCalDateToUnixTimestamp($icalDate . 'T000000');
			} else {
				list($junk, $thedate) = explode(':', $datestring);
				return parent::iCalDateToUnixTimestamp($thedate . 'T000000');
			}
		}
		return false;
	}

	/**
	 * Returns a datestring in the format YYYY/MM/DD as per MySQL default
	 * @param unknown $datestring
	 */
	public function extractDateForMySQL($datestring) {
		$datestr = false;
		$thedate = '';

		if (strlen($datestring) == 8) {			// aleady set
			$thedate = $datestring;
		}else if (strpos($datestring, 'T') !== false) {	// then we have a date in format YYYYMMDDThhmmss
			$datestring = str_replace('Z', '', $datestring);
			list($thedate, $thetime) = explode('T', $datestring);
		}else{		// we have 	VALUE=DATE:20100705
			list($junk, $thedate) = explode(':', $datestring);
		}

		if (strlen($thedate) == 8) {
			$datestr = substr($thedate, 0, 4) . '/' . substr($thedate, 4, 2) . '/' . substr($thedate, 6, 2);
		}

		return $datestr;

	}

	/**
	 * Returns a datestring in the format HH:MM:SS as per MySQL default
	 * @param unknown $timestring
	 */
	public function extractTimeForMySQL($timestring) {
		$timestr = false;
		$thetime = '';

		if (strpos($timestring, 'T') !== false) {	// then we have a time in format YYYYMMDDThhmmss
			$timestring = str_replace('Z', '', $timestring);
			list($thedate, $thetime) = explode('T', $timestring);
			$timestr = substr($thetime, 0, 2) . ':' . substr($thetime, 2, 2) . ':' . substr($thetime, 4, 2);
		}else{		// we have 	VALUE=DATE:20100705
			$timestr = '00:00:00';
		}

		return $timestr;

	}

	/**
	 *
	 */
	public function isEventDayLong($startdate, $enddate) {
		$isdaylong = 0;

		// if the startdate and the enddate are the same
		if ($startdate == $enddate) {
			$isdaylong = 1;
		} else {			// sometimes in the calendar the end date is the start of the next day
			// create one day interval
			$interval = new DateInterval('P1D');			// 1 day
			$theday = DateTime::createFromFormat('Y/m/d', $startdate);
			$theday->add($interval);
			if ($enddate == $theday->format('Y/m/d')) {
				$isdaylong = 1;
			}
		}

		return $isdaylong;
	}

	/**
	 *
	 */
	public function isEventMonthLong($startdate, $enddate) {
		$ismonthlong = 0;

		return $ismonthlong;
	}

	/**
	 *
	 */
	public function isEventYearLong($startdate, $enddate) {
		$isyearlong = 0;

		return $isyearlong;
	}

}
