<?php

class dbfunctions {

	private $mysqli = null;

	public function __construct(array $settings) {
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
}
