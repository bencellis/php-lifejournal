<?php
/**
 * This file is a template for the development of import plugins.
 *
 * @author Benjamin Ellis <benjamincellis@gmail.com>
 *
 */

class import_dummy extends import_plugins {

    private $name = 'Dummy File Import';
    private $defaultsourcename = 'Dummy';

    public function get_name() {
        return $this->name;
    }

    /**
     * This function should throw exceptions for fatal errors.
     *
     * @throws Exception
     * {@inheritDoc}
     * @see plugins::process_file()
     */
    public function process_file($filedetails, $sourcename = null) {
        $message = '';          // We should send back a success message.

        /* Remove the following line in developed plugins */
        return("This is a dummy import plugin and will not process any files.");

        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }

        if (file_exists($filedetails['tmp_name'])) {
            $recordcount = 0;
            $savedcount = 0;
            $errorcount = 0;
            /* Process the file here and save the record */

            foreach ($lines as $line) {
                $recordcount++;

                $filedate = DateTime::createFromFormat('d/m/Y', $data[0]);
                $startdate = $filedate->format('Y-m-d');

                $dbrecord = array(
                    'startdate' => $startdate, // date NOT NULL,
                    'allyear' => 0, // tinyint(1) NOT NULL DEFAULT '0',
                    'allmonth' => 0, // tinyint(1) NOT NULL DEFAULT '0',
                    'allday' => 0, // tinyint(1) NOT NULL DEFAULT '0',
                    'isevent' => 1, // tinyint(1) NOT NULL DEFAULT '0',
                    'enddate' => '', // date DEFAULT NULL,
                    'starttime' => '', // time DEFAULT '00:00:00',
                    'endtime' => '', // time DEFAULT '00:00:00',
                    'details' => '', // text CHARACTER SET utf8 NOT NULL,
                    'deleted' => 0, // tinyint(1) NOT NULL DEFAULT '0',
                    'connectedid' => '', // int(11) DEFAULT NULL,
                    'sourcetype' => $sourcename, // varchar(20) CHARACTER SET utf8 NOT NULL,
                    'sourceid' => '', // varchar(254) CHARACTER SET utf8 DEFAULT NULL
                );

                // Save the record by calling saveCalendarRecord($dbrecord)
                if (saveCalendarRecord($dbrecord)) {
                    $savedcount++;
                } else {
                    // Complain or something.
                    $errorcount++;
                }
            }       // End line processed.

        }else{
            throw new Exception('Uploaded file is missing.');
        }

        return $message;
    }
}
