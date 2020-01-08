<?php
require_once('config.php');
require_once('db_lib.php');

function markEntryAsDeleted($recid){
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	$errmsg = '';

	if (!$db->deleteJournalEntry($recid)) {
		$errmsg = 'Failed to save record because ' . $db->getLastError();
	}
	return $errmsg;
}

function unconnectJournalEntry($recid) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	$errmsg = '';

	if (!$db->unconnectJournalEntry($recid)) {
		$errmsg = 'Failed to connect records because ' . $db->getLastError();
	}

	return $errmsg;
}


function connectJournalEntries($mainid, $connectedid) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	$errmsg = '';

	// make sure the connected id actually exists
	if ($db->isEntryConnectable($connectedid)) {
		if (!$db->connectJournalEntries($mainid, $connectedid)) {
			$errmsg = 'Failed to connect records because ' . $db->getLastError();
		}
	}else{
		$errmsg = 'Connected ID cannot be used - may not exist or already connected';
	}

	return $errmsg;
}

function getJournalEntries($pagingparams) {
	global $dbconfig, $config;
	$db = new dbfunctions($dbconfig);
	$journalentries = array();

	// lets get the raw records
	if ($rawrecs = $db->getJournalEntries($pagingparams)) {
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
			$journalentry['date'] = getDisplayDate($rawrec);

			$journalentries[] = $journalentry;
		}
	}

	return $journalentries;

}

/**
 * untidy as this was copied from elewhere
 *
 * @param unknown $rawrec
 * @return mixed
 */
function getDisplayDate($rawrec) {
	$journalentry = array();

	if ($rawrec['startdate'] && $rawrec['startdate'] != '0000-00-00') {
		$phpdate = DateTime::createFromFormat('Y-m-d', $rawrec['startdate']);
		if ($rawrec['allyear']) {
			$journalentry['date'] = $phpdate->format('Y');
		}else if ($rawrec['allmonth']) {
			$journalentry['date'] = $phpdate->format('F, Y');
		}else if ($rawrec['allday']) {
			$journalentry['date'] = $phpdate->format('l j M, Y');
		}else {
			$phptime = DateTime::createFromFormat('H:i:s', $rawrec['starttime']);
			if ($phptime->format('H:i') == '00:00') {
				$journalentry['date'] = $phpdate->format('l j M, Y');
			}else{
				$journalentry['date'] = $phpdate->format('l j M, Y') . ' @ ' . $phptime->format('H:i');
			}
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
	}else{
		$journalentry['date'] = 'Undated';
	}

	return $journalentry['date'];
}

/**
 * function to work out the paging information
 */
function getPagingParams($config) {
	global $dbconfig;
	$pagingparams = array();

	session_start();			// start session
	$request = $_SESSION;			// first get the session values if any
	//die('Session is ' . print_r($_SESSION, true));

	// then we overwrite any of the values with any in the REQUEST object
	if (!empty($_REQUEST)) {
		$request = array_merge($request, $_REQUEST);
	}

	//die("This request is " . print_r($request, true));

	// defaults
	$pagingparams['page'] = empty($request['page']) ? 1 : $request['page'];
	$pagingparams['norecs'] = isset($request['norecs']) ? $request['norecs'] : $config['defaultnumrecords'];
	$pagingparams['dir'] = isset($request['dir']) ? $request['dir'] : 'ASC';		// descending default
	$pagingparams['oby'] = isset($request['oby']) ? $request['oby'] : 'startdate,starttime';	// startdate default

	// check for filtering stuff
	if (isset($request['includedeleted'])) {
		$pagingparams['includedeleted'] = $request['includedeleted'];
	}
	if (isset($request['filteryear'])) {
		if ($request['filteryear'] == 'all') {
			if (isset($pagingparams['filteryear'])) {
				unset($pagingparams['filteryear']);
			}
		}else{
			$pagingparams['filteryear'] = $request['filteryear'];
		}
	}
	if (isset($request['filtermonth'])) {
		if ($pagingparams['filteryear']) {			// only if a filter year is selected
			if ($request['filtermonth'] == 'all') {
				if (isset($pagingparams['filtermonth'])){
					unset($pagingparams['filtermonth']);
				}
			}else{
				$pagingparams['filtermonth'] = $request['filtermonth'];
			}
		}
	}

	// filtering change stuff
	if (isset($request['clearfilter'])) {			/// clear all filters
		if (isset($pagingparams['includedeleted'])) {
			unset($pagingparams['includedeleted']);
		}
		if (isset($pagingparams['filteryear'])) {
			unset($pagingparams['filteryear']);
		}
		if (isset($pagingparams['filtermonth'])) {
			unset($pagingparams['filtermonth']);
		}
// 		unset($request['trecs']);		// recount the records
// 		$pagingparams['page'] = 1; 			// leaving all the others as they are
	}

	// on any submitted form - reset the page and record numbers
	if ( isset($_POST['dosearch']) || isset($_POST['clearfilter']) || isset($_POST['filterby']) || isset($_POST['clrsearch']) ) {
		$pagingparams['page'] = 1;
		if (isset($request['trecs'])) {
			unset($request['trecs']);
		}
	}

	// are we also searching
	if (!isset($_POST['clrsearch']) && isset($request['searchterm'])) {
		$pagingparams['searchterm'] = $request['searchterm'];
	}

	// lets get the total count
	if (!isset($request['trecs'])) {
		// we go for the database count
		$db = new dbfunctions($dbconfig);
		$pagingparams['trecs'] = $db->getJournalEntriesCount($pagingparams);		// startdate default
	}else{
		$pagingparams['trecs'] = $request['trecs'];
	}

	$_SESSION = $pagingparams;

	//die('Session is ' . print_r($_SESSION, true));

	return $pagingparams;
}

/**
 * Get the paging bar
 *
 * @param unknown $url
 * @param unknown $pagingparms
 */
function getPagingBar($paginglink, $pagingparms) {
	//we keep eveything the same and change the page no
	$pagingbar = '';
	if ($pagingparms['trecs'] > $pagingparms['norecs']) {
		if (!$pagingparms['page']) {
			$pagingparms['page'] = 1;
		}
		$pagingbar = handle_pagination($pagingparms['trecs'], $pagingparms['page'], $pagingparms['norecs'], $paginglink . '?page=');
	}

	return $pagingbar;
}
/**
 * stolen code ammended to do the page numbers as expected
 *
 * @param unknown $total
 * @param unknown $page
 * @param unknown $shown
 * @param unknown $url
 */
function handle_pagination($total, $page, $shown, $url) {
	$pages = ceil( $total / $shown );

	$range_start = ( ($page >= 5) ? ($page - 3) : 1 );
	$range_end = ( (($page + 5) > $pages ) ? $pages : ($page + 5) );


	$r[] = '<span><a href="'. $url .'1">&laquo; first</a></span>';
	if ( $page > 1 ) {
		$r[] = '<span><a href="'. $url . ( $page - 1 ) .'">&lsaquo; previous</a></span>';
		$r[] = ( ($range_start > 1) ? ' ... ' : '' );
	}

	if ( $range_end > 1 ) {
		foreach(range($range_start, $range_end) as $key => $value) {
			if ($value == $page) {
				$r[] = '<span>'. $value .'</span>';
			} else {
				$r[] = '<span><a href="'. $url . ($value) .'">'. $value .'</a></span>';
			}
		}
	}

	if ( ( $page + 1 ) < $pages ) {
		$r[] = ( ($range_end < $pages) ? ' ... ' : '' );
		$r[] = '<span><a href="'. $url . ( $page + 1 ) .'">next &rsaquo;</a></span>';
		$r[] = '<span><a href="'. $url . ( $pages - 1 ) .'">last &raquo;</a></span>';
	}

	return ( (isset($r)) ? '<div>'. implode("\r\n", $r) .'</div>' : '');
}

function getEmptyRecord($today = null) {
	return array(
		'recid' => 0,
		'startdate' => $today ? $today->format('Y-m-d') : 0,
		'allyear' => 0,
		'allmonth' => 0,
		'allday' => 0,
		'enddate' => $today ? $today->format('Y-m-d') : 0,
		'starttime' => 0,
		'isevent' => 0,
		'endtime' => 0,
		'details' => '',
		'deleted' => 0,
		'sourcetype' => 'Manual',
	);
}

function getSubmittedRecord($params) {
	$record = getEmptyRecord();

	// TODO deal with the date and time fields
// 	$params['startYear'] = 1;
// 	$params['startMonth'] = 1;
// 	$params['startDay'] = 1;
// 	$params['startHour'] = 0;
// 	$params['startMinute'] = 0;

// 	$params['endYear'] = 1;
// 	$params['endMonth'] = 1;
// 	$params['endDay'] = 1;
// 	$params['endHour'] = 0;
// 	$params['endMinute'] = 0;


	foreach($record as $fld => $val) {
		//if (isset($params[$fld])) {
			$record[$fld] = $params[$fld];
		//}
	}
	$separator = '-';
	if ($startdate = _makeSQLDate($params['startYear'], $params['startMonth'], $params['startDay'], $separator)) {
		$record['startdate'] = $startdate;
	}
	if ($enddate = _makeSQLDate($params['endYear'], $params['endMonth'], $params['endDay'], $separator)) {
		$record['enddate'] = $enddate;
	}

	if ($starttime = _makeSQLtime($params['startHour'], $params['startMinute'])) {
		$record['starttime'] = $starttime;
	}
	if ($endtime = _makeSQLtime($params['endHour'], $params['endMinute'])){
		$record['endtime'] = $endtime;
	}

	return $record;
}

function getJounalEntry($recid) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	return $db->getJournalEntry($recid);
}

function getJounalEntryWithConnections($recid) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	return $db->getJounalEntryWithConnections($recid);
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

function journalRecordExists($sourcetype, $sourceid) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);

	$exists = false;
	if ($sourceid) {			// if there is no source id - we cannot check
		if ($db->getJournalBySourceId($sourcetype, $sourceid)) {
			$exists = true;
		}
	}

	return $exists;
}

function saveCalendarRecord($record) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);

	if (!$ret = $db->saveJournalEntry($record)) {
		error_log($db->getLastError());
	}

	return $ret;
}

function getAllEntryYears() {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	return $db->getAllEntryYears();
}


function ProcessPostData($params) {
	global $dbconfig;
	$db = new dbfunctions($dbconfig);
	$errorstr = '';

	//echo '<pre>' . print_r($params, true) . '</pre>';

	if (!empty($params['details']))	{			// no empty records
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
				if (!empty($params['isevent'])) {
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
				}else{
					// ensure we update end record for existing records
					if ($dbparams['recid']) {
						$dbparams['enddate'] = 'null';
						$dbparams['endtime'] = "00:00:00";
					}
				}
			}else{
				if ($dbparams['recid']) {
					// we need to reset all the date and fields
					$dbparams['startdate'] = 0;
					$dbparams['starttime'] = "00:00:00";
					$dbparams['enddate'] = 'null';
					$dbparams['endtime'] = "00:00:00";
				}
			}

			// before we do this - lets make sure enddate < startdate
			if (isset($dbparams['enddate']) && ($dbparams['enddate'] != 'null') && (!_checkDateTimes($dbparams['startdate'], $dbparams['starttime'], $dbparams['enddate'], $dbparams['endtime']))) {
				$errorstr = 'End Date cannot be earlier than Start Date';
			}

			// now can we save the details?
			if (!$errorstr && count($dbparams)) {
				//die('<pre>' . print_r($dbparams, true) . '</pre>');
				if (!$db->saveJournalEntry($dbparams)) {
					$errorstr = 'Failed to save record: ' . $db->getLastError();
				}
			}
		}
	}else{
		$errorstr = 'No entry details have been entered';
	}

	return $errorstr;
}

function _checkDateTimes($startdate, $starttime, $enddate, $endtime) {
	$noerror = false;
	if ($start = DateTime::createFromFormat("Y/m/d H:i:s", "$startdate $starttime")) {
		if ($end = DateTime::createFromFormat("Y/m/d H:i:s", "$enddate $endtime")) {
			if ($start->getTimestamp() <= $end->getTimestamp()) {		// compare them
				$noerror = true;
			}
		}
	}
	return $noerror;
}

function _makeSQLDate($year, $month, $day, $separator = '/') {
	$datestr = '';
	//bool checkdate ( int $month , int $day , int $year )
	if (checkdate($month, $day, $year)) {
		$datestr = $year . $separator . sprintf("%02d", $month) . $separator . sprintf("%02d", $day);
	}
	return $datestr;
}

function _makeSQLTime($hour, $minutes, $separator = ':') {
	$timestr = '';

	if (($hour >= 0 && $hour <= 23) && ($minutes >= 0 && $minutes <= 59)) {
		$timestr = sprintf("%02d", $hour) . $separator . sprintf("%02d", $minutes) . $separator . '00';
	}

	return $timestr;

}
