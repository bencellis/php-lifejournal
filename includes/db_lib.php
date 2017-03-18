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

	/*
	  recid int(10) unsigned NOT NULL AUTO_INCREMENT,
	  startdate date DEFAULT NULL,
	  allyear tinyint(1) NOT NULL DEFAULT '0',
	  allmonth tinyint(1) NOT NULL DEFAULT '0',
	  allday tinyint(1) NOT NULL DEFAULT '0',
	  isevent tinyint(1) NOT NULL DEFAULT '0',
	  enddate date DEFAULT NULL,
	  starttime time DEFAULT '00:00:00',
	  endtime time DEFAULT '00:00:00',
	  details text NOT NULL,
	  deleted tinyint(1) NOT NULL DEFAULT '0',
	  connectedid int(11) NOT NULL,
	  sourcetype varchar(20) NOT NULL,
	  sourceid varchar(254) DEFAULT NULL,
	 */

	function saveJournalEntry($params) {
		$sqlfields = '';
		foreach ($params as $fld => $val) {
			if ($fld != 'recid') {
				if ($sqlfields) {
					$sqlfields .= ', ';
				}
				// we have to deal with date and time fields TODO

				$val = (is_numeric($val)) ? $val : "'$val'";
				$sqlfields .= "$fld = $val";
			}
		}

		$sql = '';
		if (empty($params['recid'])) {
			$sql = "INSERT INTO journal SET " . $sqlfields;
		}else{
			$sql = "UPDATE journal SET " . $sqlfields . "WHERE recid = " . $recid;
		}
		return($this->mysqli->query($sql));
	}

	public function getJournalEntries($pagingparams, $deleted = false) {
		$journalentries = array();

		$sql = 'SELECT * FROM journal';

		$delfldval = $deleted ? 1 : 0;
		$sql .= " WHERE deleted = $delfldval";
/*
	$pagingparams['page'] = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
	$pagingparams['norecs'] = isset($_REQUEST['norecs']) ? $_REQUEST['norecs'] : $config['defaultnumrecords'];
	$pagingparams['dir'] = isset($_REQUEST['dir']) ? $_REQUEST['dir'] : 'DESC';		// descending default
	$pagingparams['oby'] = isset($_REQUEST['oby']) ? $_REQUEST['oby'] : 'datestart';	// datestart default
*/
		$sql .= ' ORDER BY ' . $pagingparams['oby'] . ' ' . $pagingparams['dir'];

		$limitstart = $pagingparams['page'] - 1;
		$limitto = $pagingparams['page'] * $pagingparams['norecs'];

		$sql .= " LIMIT $limitstart, $limitto";

		if ($results = $this->mysqli->query($sql)) {
			while ($result = $results->fetch_assoc()) {
				$journalentries[] = $result;
			}
		}

		return $journalentries;
	}
}
