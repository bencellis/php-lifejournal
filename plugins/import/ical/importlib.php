<?php

require('lib/iCalendarParser.php');


class import_ical extends import_plugins {

    private $name = 'Calendar ical Format Import';
    private $defaultsourcename = 'Calendar';

    public function get_name() {
        return $this->name;
    }

    public function process_file($filedetails, $sourcename = null) {

        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }

        // Is this a iCal file?
        $message = 'Processed upload file';
        if ($filedetails['type'] != 'text/calendar') {
            throw new Exception('Can only process iCal files');
        }

        if (file_exists($filedetails['tmp_name'])) {

            $ical = new iCalendar($filedetails['tmp_name']);

            $events = $ical->events();

            $noofevents = $ical->event_count;
            $errorcount = 0;
            $eventcount = 0;
            $recordcnt = 0;

			foreach ($events as $event) {
			    $eventcount++;
				$record = array();

				// source is Calendar
				$record['sourcetype'] = $sourcename;

				//uid
				if ($event['UID']) {
					$record['sourceid'] = $event['UID'];
				}else{
				    continue;       // don't save anything without an id!!!
				}

				// let's not waste time if we have seen this record before
				// if the record is changed later too bad
				if (!journalRecordExists($record['sourcetype'], $record['sourceid'])) {

					// the detail is $event['SUMMARY'] + $event['DESCRIPTION'] + $event['LOCATION'];
					$record['details'] = $event['SUMMARY'];
					$record['details'] .=  $event['DESCRIPTION'] ? ' ' .  $event['DESCRIPTION'] : '';
					$record['details'] .=  $event['LOCATION'] ? ' at ' .  $event['LOCATION'] : '';
					// for some reason the Google iCal escapes &amp; as &amp\; - changed
					$record['details'] = str_replace('\;', ';', $record['details']);
					// it also escapes , with \,
					$record['details'] = str_replace('\,', ',', $record['details']);

					$record['startdate'] = $ical->extractDateForMySQL($event['DTSTART']);
					$record['starttime'] = $ical->extractTimeForMySQL($event['DTSTART']);

					if (isset($event['DTEND'])) {
						$record['enddate'] = $ical->extractDateForMySQL($event['DTEND']);
						$record['endtime'] = $ical->extractTimeForMySQL($event['DTEND']);
					}

					if (empty($record['enddate']) && ($record['starttime'] != '00:00:00')) {
						$record['isevent'] = 1;
					}else if (isset($record['enddate']) && $record['starttime'] == '00:00:00' && $record['endtime'] == '00:00:00') {
						if (!$record['allday'] = $ical->isEventDayLong($record['startdate'], $record['enddate'])) {			// date and next day
							if (!$record['allmonth'] = $ical->isEventMonthLong($record['startdate'], $record['enddate'])) {
								if ($record['allyear'] = $ical->isEventYearLong($record['startdate'], $record['enddate']));
							}
						}
						// if any of them are set remove $endtimes
						if (!empty($record['allday']) || !empty($record['allmonth']) || !empty($record['allyear'])) {
							unset($record['enddate']);
							unset($record['endtime']);
						}
					}

				    if (saveCalendarRecord($record)) {
				        $recordcnt++;
				    }else{
				    	$errorcount++;
				    }
				}
			}
			if($errorcount) {
			    $message .= " - ($errorcount Error lines)";
			}else{
			    $message .= " - No errors encountered";
			}
			$message .= " - Added $recordcnt of $noofevents entries.";
        }else{
            throw new Exception('Uploaded file is missing.');
        }

        return $message;
    }

}

