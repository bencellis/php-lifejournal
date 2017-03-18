<?php
require_once('config.php');
require_once('db_lib.php');

function getEntryEditLinks(){

}




function getJournalEntries($pagingparams, $deleted = false) {
	global $dbconfig, $config;
	$db = new dbfunctions($dbconfig);
	$journalentries = array();

	// lets get the raw records
	if ($rawrecs = $db->getJournalEntries($pagingparams, $deleted)) {
		foreach ($rawrecs as $rawrec) {
			// massage the data
			$journalentry['recid'] = $rawrec['recid'];
			$journalentry['deleted'] = $rawrec['deleted'] ? 'Deleted' : 'No';
			$journalentry['source'] = $rawrec['sourcetype'];
			$journalentry['realdate'] = $rawrec['startdate'];		// for display purposes

			// details
			$detaillength = $config['detaillength'];
			if (strlen($rawrec['details']) > $detaillength) {
				// reduce length
				$journalentry['details'] = substr($rawrec['details'], 0, $detaillength);
				// now add elipses from last space
				$journalentry['details'] = substr($rawrec['details'], 0, strrpos($journalentry['details'], ' ')) . ' ...';
			}else{
				$journalentry['details'] = $rawrec['details'];
			}


			// date stuff
			if ($rawrec['startdate']) {
				$phpdate = DateTime::createFromFormat('Y-m-d', $rawrec['startdate']);
				if ($rawrec['allyear']) {
					$journalentry['date'] = $phpdate->format('Y');
				}else if ($rawrec['allmonth']) {
					$journalentry['date'] = $phpdate->format('F, Y');
				}else if ($rawrec['allday']) {
					$journalentry['date'] = $phpdate->format('l j M, Y');
				}else {
					$phptime = DateTime::createFromFormat('H:i:s', $rawrec['starttime']);
					$journalentry['date'] = $phpdate->format('l j M, Y') . ' @ ' . $phptime->format('H:i');
				}
			}else{
				$journalentry['date'] = 'Not Dated';
			}

			// only if available
			if ($rawrec['enddate']) {
				$journalentry['date'] .=  '<br />To ';
				$phpdate = DateTime::createFromFormat('Y-m-d', $rawrec['enddate']);
				$journalentry['date'] .= $phpdate->format('l j M, Y');
				if ($rawrec['endtime'] !== '00:00:00') {
					$phptime = DateTime::createFromFormat('H:i:s', $rawrec['endtime']);
					$journalentry['date'] .= ' @ ' . $phptime->format('H:i');
				}
			}
			$journalentries[] = $journalentry;
		}
	}

	return $journalentries;

}


/**
 * function to work out the paging information
 */
function getPagingParams($config) {
	$pagingparams = array();

	$request = isset($_REQUEST['paging']) ? unserialize(base64_decode(urldecode($_REQUEST['paging']))) : $_REQUEST;

	$pagingparams['page'] = isset($request['page']) ? $request['page'] : 1;
	$pagingparams['norecs'] = isset($request['norecs']) ? $request['norecs'] : $config['defaultnumrecords'];
	$pagingparams['dir'] = isset($request['dir']) ? $request['dir'] : 'DESC';		// descending default
	$pagingparams['oby'] = isset($request['oby']) ? $request['oby'] : 'startdate';	// startdate default

	//this is one that does not get passed around but is used in links
	$paging = urlencode(base64_encode(serialize($pagingparams)));
	$pagingparams['paging'] = $paging;

	return $pagingparams;
}

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
function RedirectTo($url, $permanent = false){
	//if (headers_sent() === false){
		header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
	//}
	die('You are being redirected');			// need to die to ensure no further processing
}

function ProcessPostData($params) {
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