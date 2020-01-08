<?php

require_once('includes/lib.php');
require('iCalendarParser.php');

//$ical   = new iCalendar('datatest/MyCal.ics');
$ical   = new iCalendar('datatest/benjamin.c.ellis@googlemail.com_benjamin.c.ellis@gmail.com.ics');
//$ical   = new iCalendar('datatest/mukudu Timesheet_423ml0252v128uhf2q02shlnuo@group.calendar.google.com.ics');
//$ical   = new iCalendar('datatest/Jen Shifts_eonnmicrkd2uvko2e1g8l9qqug@group.calendar.google.com.ics');

//$events = array_slice($ical->events(), 0, 20);
$events = $ical->events();

//die('<pre>' . print_r($events, true) . '</pre>');

$sdate = $ical->extractDateForMySQL($events[0]['DTSTART']);
$noofevents = $ical->event_count;

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

    <div class="container">
	      <div class="row">
	        <div class="col-md-12">
	        	<p>Processing <?php echo $noofevents; ?> Events, starting from <?php echo $sdate; ?></p>
	         </div>
	      </div>
	      <div class="row">
	        <div class="col-md-12">
				<div>
				<?php
					foreach ($events as $event) {
						$record = array();

						// source is Calendar
						$record['sourcetype'] = 'Calendar';

						//uid
						if ($event['UID']) {
							$record['sourceid'] = $event['UID'];
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

						    //echo '<pre>' . print_r($record, true) . "</pre><br/>";
 						    if (saveCalendarRecord($record)) {
						    	echo "<p>Record '" . $record['sourceid'] . "' successfully saved</p>";
						    }else{
						    	echo "<p>Failed to save record '" . $record['sourceid'] . "'</p>";
						    }
						}else{
							echo "<p>Record '" . $record['sourceid'] . "' already exists</p>";
						}
						//echo "<hr/>";
					}
				?>
				</div>
	        </div>
	      </div>

    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>
