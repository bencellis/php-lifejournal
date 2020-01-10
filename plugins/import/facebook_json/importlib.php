<?php
/**
 *
 * @author Benjamin Ellis
 *
 */

class import_facebook_json extends import_plugins {

    private $name = 'Facebook JSON Timeline Import';
    private $defaultsourcename = 'FacebookTL';

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
        $message = 'Successfully Processed file';          // We should send back a success message.

        if ($filedetails['type'] != 'application/json') {
            throw new Exception('Can only process JSON files');
        }

        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }

        if (file_exists($filedetails['tmp_name'])) {
            $recordcount = 0;
            $savedcount = 0;
            $errorcount = 0;
            /* Process the file here and save the record */

            $filecontents = file_get_contents($filedetails['tmp_name']);
            $posts = json_decode($filecontents, true);

            if ($posts === null) {
                throw new Exception(getJSONerror(json_last_error()));
            }

            $missrecords = array(' shared ', ' likes ', 'now friends', ' went to ', ' wrote on ');

            foreach ($posts as $post) {
                $recordcount++;

                // Check which records we want.
                if (empty($post['title'])) {
                    //die ('<pre>' . print_r($post, true) . '</pre>');
                    continue;
                }

                if (empty($post['data'])) {
                    //die ('<pre>' . print_r($post, true) . '</pre>');
                    continue;
                }

                // if there is a comment - we always save the record - else we see if its a record to miss
                foreach ($missrecords as $missthis) {
                    if (stripos($post['title'], $missthis) !== false) {
                        continue(2);        //Back to Posts
                    }
                }

                $posttext = '';
                // Anything without a post is being ignored.
                foreach ($post['data'] as $datarecord) {
                    foreach ($datarecord as $key => $data) {
                        if ($key == 'post') {
                            if ($posttext) {
                                $posttext .= "\n";
                            }
                            $posttext .= $data;
                        }
                    }
                }

                if (!$posttext) {
                    continue;
                }

                if (!journalRecordExists($sourcename, $post['timestamp'])) {
                    if ($postdate = DateTime::createFromFormat('U', $post['timestamp'])) {

                        $dbrecord = array(
                            'details' => $posttext,
                            'startdate' => $postdate->format('Y-m-d'), // date NOT NULL,
                            'starttime' => $postdate->format('H:i:s'), // time DEFAULT '00:00:00',
                            'sourcetype' => $sourcename,
                            'sourceid' => $post['timestamp'], // varchar(254) CHARACTER SET utf8 DEFAULT NULL
                            'isevent' => 1
                        );

                        // Save the record by calling saveCalendarRecord($dbrecord)
                        if (saveCalendarRecord($dbrecord)) {
                            $savedcount++;
                        } else {
                            // Complain or something.
                            $errorcount++;
                        }
                    } else {
                        $errorcount++;
                    }
                }
            }       // End line processed.
            $message .= " - Processed $recordcount records - Saved $savedcount - $errorcount Errors";
        }else{
            throw new Exception('Uploaded file is missing.');
        }

        return $message;
    }
}
