<?php


class import_csv extends import_plugins {

    private $name = 'CSV Import';
    // private $defaultsourcename = 'CSVimport';
    private $defaultsourcename = 'HMonitor';

    public function get_name() {
        return $this->name;
    }

    public function process_file($filedetails, $sourcename = null) {
        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }
        // Is this a CSV file?
        $message = 'Processed upload file - ';
        if ($filedetails['type'] != 'text/csv') {
            throw new Exception('Can only process CSV files');
        }



        if (file_exists($filedetails['tmp_name'])) {
            // Check we have it in the right format;
            // "Date","Time","Notes"
            $headingread = false;
            $linecnt = 0;
            $emptycount = 0;
            $errorcount = 0;
            $handle = fopen($filedetails['tmp_name'], 'r');
            while ( ($data = fgetcsv($handle) ) !== FALSE ) {
                $linecnt++;
                if (!$headingread) {
                    if ($data[0] !== 'Date' || $data[1] !== 'Notes') {
                        throw new Exception('CSV is in incorrect format - Date, Notes Required');
                    }
                    $headingread = true;
                    continue;
                }
                /*
                 * CREATE TABLE `journal` (
                      `recid` int(10) UNSIGNED NOT NULL,
                        `startdate` date NOT NULL,
                      `allyear` tinyint(1) NOT NULL DEFAULT '0',
                      `allmonth` tinyint(1) NOT NULL DEFAULT '0',
                        `allday` tinyint(1) NOT NULL DEFAULT '0',
                      `isevent` tinyint(1) NOT NULL DEFAULT '0',
                      `enddate` date DEFAULT NULL,
                      `starttime` time DEFAULT '00:00:00',
                      `endtime` time DEFAULT '00:00:00',
                        `details` text CHARACTER SET utf8 NOT NULL,
                      `deleted` tinyint(1) NOT NULL DEFAULT '0',
                      `connectedid` int(11) DEFAULT NULL,
                      `sourcetype` varchar(20) CHARACTER SET utf8 NOT NULL,
                      `sourceid` varchar(254) CHARACTER SET utf8 DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                 */
                // Let's deal with time
                if (!$thedate = DateTime::createFromFormat('d/m/Y', $data[0])) {
                    throw new Exception("Invalid Date Format on line $linecnt");
                }
                if (!$note = trim($data[1])) {
                    // Empty Note - ignore this line.
                    $emptycount++;
                    continue;
                }
                $dbrecord = array();
                $dbrecord['startdate'] = $thedate->format('Y-m-d');
                $dbrecord['allday'] = 1;
                $dbrecord['sourcetype'] = $sourcename;
                $dbrecord['sourceid'] = $thedate->getTimestamp();
                $dbrecord['details'] = $note;

                if (!saveCalendarRecord($dbrecord)) {
                    $errorcount++;
                }
            }
            fclose($handle);
            if ($emptycount) {
                $message .= "($emptycount Empty lines)";
            }
            if($errorcount) {
                $message .= "($errorcount Error lines)";
            }else{
                $message .= ' No errors encountered';
            }
        }else{
            throw new Exception('Uploaded file is missing.');
        }

        return $message;

    }

}