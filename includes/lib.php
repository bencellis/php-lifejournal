<?php

require_once('db_lib.php');


function getEmptyRecord($today = null) {
	return array(
		'recid' => 0,
		'startdate' => $today ? $today->format('d-m-Y') : 0,
		'allyear' => 0,
		'allmonth' => 0,
		'allday' => 0,
		'enddate' => 0,
		'starttime' => 0,
		'isEvent' => 0,
		'endtime' => 0,
		'details' => '',
		'deleted' => 0,
		'sourcetype' => 'Manual',
	);
}

function getSubmittedRecord($params) {
	$record = getEmptyRecord();

	// TODO deal with the date and time fields
	foreach($record as $fld => $val) {
		if (isset($params[$fld])) {
			$record[$fld] = $params[$fld];
		}
	}

	return $record;
}

/**
 * Function to redirect browser
 *
 * @param str $url
 * @param bool $permanent status of redirect
 */
function redirect($url, $permanent = false){
	//if (headers_sent() === false){
		header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
	//}
	die('You are being redirected');			// need to die to ensure no further processing
}

function process_postdata($params) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	$errorstr = '';

	//echo '<pre>' . print_r($params, true) . '</pre>';

	// deal with a delete first - all we need is recid
	if (isset($params['deleterecord'])) {
		if (empty($params['recid'])) { // should not happen
			$errorstr = 'Record Id Needs to be specified';
		}else{
			// delete the record
			if (!$db->deleteJournalEntry($params['recid'])){
				$errorstr = 'Failed to delete record';
			}
		}
	}else if (isset($params['saverecord'])){
		//die('<pre>' . print_r($params, true) . '</pre>');

		// none massaged data
		$dbparams = array(
			'details' => $params['details'],
			'sourcetype' => empty($params['sourcetype']) ? 'Manual' : $params['sourcetype'],
		);

		//only specify if we have it - determines INSERT or UPDATE
		if ($params['recid']) {
			$dbparams['recid'] = $params['recid'];
		}

		// start year
		$setenddate = true;			// will we need an end date and time
		if ($params['startYear'] > 0) {
			if (!empty($params['allYear'])) {
				$dbparams['allyear'] = 1;
				$setenddate  = false;

				// reset the following fields
				$params['startMonth'] = 1;
				$params['startDay'] = 1;
				$params['startHour'] = 0;
				$params['startMinute'] = 0;
				if (!$dbparams['startdate'] = _makeSQLDate($params['startYear'], $params['startMonth'], $params['startDay'])){
					$errorstr = 'Start Date is invalid';
				}
			}else if (!empty($params['allMonth'])) {
				$dbparams['allmonth'] = 1;
				$setenddate  = false;

				// reset the following fields
				$params['startDay'] = 1;
				$params['startHour'] = 0;
				$params['startMinute'] = 0;
				if (!$dbparams['startdate'] = _makeSQLDate($params['startYear'], $params['startMonth'], $params['startDay'])){
					$errorstr = 'Start Date is invalid';
				}
			}else if (!empty($params['allDay'])) {
				$dbparams['allday'] = 1;
				$setenddate  = false;

				// reset the following fields
				$params['startHour'] = 0;
				$params['startMinute'] = 0;
				if (!$dbparams['startdate'] = _makeSQLDate($params['startYear'], $params['startMonth'], $params['startDay'])) {
					$errorstr = 'Start Date is invalid';
				}
			}else{
				// set up the dates and times
				if (!$dbparams['startdate'] = _makeSQLDate($params['startYear'], $params['startMonth'], $params['startDay'])) {
					$errorstr = 'Start Date is invalid';
				}
				if (!$dbparams['starttime'] = _makeSQLTime($params['startHour'], $params['startMinute'])){
					$errorstr = 'Start Time is invalid';
				}
			}

			// end date stuff
			if (!empty($params['isEvent'])) {
				$dbparams['isevent'] = 1;
				$setenddate = false;
			}

			if ($setenddate) {
				if (!$dbparams['enddate'] = _makeSQLDate($params['endYear'], $params['endMonth'], $params['endDay'])) {
					$errorstr = 'End Date is invalid';
				}
				if (!$dbparams['endtime'] = _makeSQLTime($params['endHour'], $params['endMinute'])){
					$errorstr = 'End Time is invalid';
				}
				if (!$errorstr) {
					// check if we have the same day and time
					if (($dbparams['startdate'] == $dbparams['enddate']) && ($dbparams['starttime'] == $dbparams['endtime'])) {
						if ($dbparams['starttime'] == "00:00:00") {		// only if no time has been set
							$dbparams['allday'] = 1;
						}else{
							$dbparams['isevent'] = 1;
						}
						unset($dbparams['enddate']);
						unset($dbparams['endtime']);
					}
				}
			}

			// now can we save the details?
			if (!$errorstr) {
				//die('<pre>' . print_r($dbparams, true) . '</pre>');
				if (!$db->saveJournalEntry($dbparams)) {
					$errorstr = 'Failed to save record: ' . $db->getLastError();
				}
			}
		}
	}

	return $errorstr;
}

function _makeSQLDate($year, $month, $day) {
	$datestr = '';
	//bool checkdate ( int $month , int $day , int $year )
	if (checkdate($month, $day, $year)) {
		$datestr = $year . '/'. sprintf("%02d", $month) . '/' . sprintf("%02d", $day);
	}
	return $datestr;
}

function _makeSQLTime($hour, $minutes) {
	$timestr = '';

	if (($hour >= 0 && $hour <= 23) && ($minutes >= 0 && $minutes <= 59)) {
		$timestr = sprintf("%02d", $hour) . ':' . sprintf("%02d", $minutes) . ':00';
	}

	return $timestr;

}