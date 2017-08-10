<?php

class dbfunctions {

	private $mysqli = null;

	public function __construct(array $settings, $forcenew = false) {
		// TODO if we already exist return existing connection
		$message = '';
		$this->mysqli = new mysqli($settings['dbserver'], $settings['dbuser'], $settings['dbpasswd'], $settings['dbname'], $settings['dbport']);
		if ($this->mysqli->connect_errno) {
			$message =  "Failed to connect to MySQL: (" .  $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
		}elseif ($this->mysqli->error) {
			$message =  "Failed to connect to MySQL: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
		}

		if ($message) {
			die($message);
		}
	}

	public function getLastError() {
		return "(" . $this->mysqli->errno . ") " . $this->mysqli->error;
	}


	/*
	 * JOURNAL table functions
	 *
	 */

	/**
	 * Function to mark a journal entry as deleted
	 * @param int $recid
	 */
	public function deleteJournalEntry($recid) {
		$sql = "UPDATE journal SET deleted = 1 WHERE recid = $recid";
		return($this->mysqli->query($sql));
	}

	public function getAllEntryYears() {
		$years = array();
		$sql = "SELECT DISTINCT(YEAR(startdate)) AS theyear FROM journal";
		if ($results = $this->mysqli->query($sql)) {
			while ($result = $results->fetch_assoc()) {
				$years[] = $result['theyear'];
			}
		}
		return $years;
	}

	/*
	  recid int(10) unsigned NOT NULL AUTO_INCREMENT,
	  startdate date NOT NULL,
	  allyear tinyint(1) NOT NULL DEFAULT '0',
	  allmonth tinyint(1) NOT NULL DEFAULT '0',
	  allday tinyint(1) NOT NULL DEFAULT '0',
	  isevent tinyint(1) NOT NULL DEFAULT '0',
	  enddate date DEFAULT NULL,
	  starttime time DEFAULT '00:00:00',
	  endtime time DEFAULT '00:00:00',
	  details text NOT NULL,
	  deleted tinyint(1) NOT NULL DEFAULT '0',
	  connectedid int(11) DEFAULT NULL,
	  sourcetype varchar(20) NOT NULL,
	  sourceid varchar(254) DEFAULT NULL,
	 */

	function saveJournalEntry($params) {
		$sqlfields = '';
		$recid = 0;
		if (isset($params['recid'])) {
			$recid = $params['recid'];
			unset($params['recid']);
		}

		// we need to massage the $params to ensure any changes to the Boolean fields are reflected correctly
		$boolfields = array('allyear', 'allmonth', 'allday',  'isevent');
		foreach($boolfields as $bf) {
			if (isset($params[$bf])) {
				if (empty($params['startdate'])) {
					$params[$bf] = 0;		// cannot have an all field if undated
				}// else the setting value stands
				// TODO only one of these fields can be set

			}else{
				$params[$bf] = 0;
			}
		}

		foreach ($params as $fld => $val) {
			if ($fld != 'recid') {
				if ($sqlfields) {
					$sqlfields .= ', ';
				}
				// TODO we have to deal with date and time fields

				if ($val == 'null') {
					$sqlfields .= "$fld = null";
				}else{
					$val = (is_numeric($val)) ? $val : "'" . $this->mysqli->real_escape_string($val) . "'";
					$sqlfields .= "$fld = $val";
				}
			}else{
				$recid = $val;
			}
		}

		$sql = '';
		if ($recid == 0) {
			$sql = "INSERT INTO journal SET " . $sqlfields;
		}else{
			$sql = "UPDATE journal SET " . $sqlfields . " WHERE recid = " . $recid;
		}

		return($this->mysqli->query($sql));
	}


	public function getJournalEntriesCount($pagingparams) {
		$count = false;

		$sql = 'SELECT COUNT(*) AS count FROM journal ' . $this->_getEntriesSubSQL($pagingparams);

		if ($results = $this->mysqli->query($sql)) {
			$result = $results->fetch_assoc();
			$count = $result['count'];
		}

		return $count;
	}

	private function _getEntriesSubSQL($pagingparams) {
		$sql = '';
		// do filtering
		if (!isset($pagingparams['includedeleted'])) {
			$sql .= ($sql ? ' AND ' : ' WHERE ') . 'deleted = 0';
		}

		if (isset($pagingparams['filteryear'])) {
			$sql .= ($sql ? ' AND ' : ' WHERE ') . 'YEAR(startdate) = ' . $pagingparams['filteryear'];
			if (isset($pagingparams['filtermonth'])) {
				$sql .= ' AND MONTH(startdate) = ' . $pagingparams['filtermonth'];
			}
		}
		return $sql;
	}

	public function getJournalEntries($pagingparams) {
		$journalentries = array();

		$sql = 'SELECT * FROM journal ' . $this->_getEntriesSubSQL($pagingparams);

		// ordering
		$ordersql = '';
		$orderbys = explode(',', $pagingparams['oby']);
		if (count($orderbys)) {
			foreach ($orderbys as $orderby) {
				if ($ordersql) {
					$ordersql .= ', ';
				}
				$ordersql .= $orderby . ' ' . $pagingparams['dir'];
			}
			$sql .= ' ORDER BY ' . $ordersql;
		}

		// paging
		$limitstart = ($pagingparams['page'] - 1) * $pagingparams['norecs'];
		$limitto = $pagingparams['page'] * $pagingparams['norecs'];
		$sql .= " LIMIT $limitstart, $limitto";

		//error_log($sql);

		if ($results = $this->mysqli->query($sql)) {
			while ($result = $results->fetch_assoc()) {
				$journalentries[] = $result;
			}
		}

		return $journalentries;
	}

	public function isEntryConnectable($connectedid) {
		$connectable = false;

		$sql = "SELECT connectedid FROM journal WHERE recid = $connectedid";
		if ($results = $this->mysqli->query($sql)) {
			if ($result = $results->fetch_assoc()) {		// only one record
				$connectable = $result['connectedid'] ? false : true;
			}
		}

		return $connectable;
	}

	public function connectJournalEntries($recid, $connectedid) {
		$sql = "UPDATE journal SET connectedid = $connectedid, deleted = 1 WHERE recid = $recid";
		return $this->mysqli->query($sql);
	}

	function getJournalEntry($recid) {
		$record = null;
		$sql = "SELECT * FROM journal WHERE recid = $recid";
		if ($results = $this->mysqli->query($sql)) {
			$record = $results->fetch_assoc();		// only one record
		}

		return $record;
	}

	function getJournalBySourceId($sourcetype, $sourceid) {
		$record = null;
		$sql = "SELECT * FROM journal WHERE sourcetype LIKE '$sourcetype' AND sourceid LIKE '$sourceid'";
		if ($results = $this->mysqli->query($sql)) {
			$record = $results->fetch_assoc();		// only one record
		}
		return $record;
	}

}
