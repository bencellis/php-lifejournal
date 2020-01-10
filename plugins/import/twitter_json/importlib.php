<?php
/**
 *
 * @author Benjamin Ellis
 *
 */

class import_twitter_json extends import_plugins {

    private $name = 'Twitter Tweets JSON import';
    private $defaultsourcename = 'Twitter';

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

        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }

        if ($filedetails['type'] != 'application/x-javascript') {
            throw new Exception('Can only process JSON files');
        }

        if (file_exists($filedetails['tmp_name'])) {
            $recordcount = 0;
            $savedcount = 0;
            $errorcount = 0;
            /* Process the file here and save the record */

            $filecontents = file_get_contents($filedetails['tmp_name']);
            $filecontents = substr($filecontents, strpos($filecontents, '['));
            $tweets = json_decode($filecontents, true);

            if ($tweets === null) {
                throw new Exception(getJSONerror(json_last_error()));
            }

            $fbsourceurl = 'Facebook';
            $excludereweets = true;

            foreach ($tweets as $tweet) {
                $recordcount++;

                /* Ignore any tweets we do not want to save. */

                // if the tweet is in reply to someone - ignore
                if (!empty($tweet['in_reply_to_status_id']) || !empty($tweet['in_reply_to_user_id'])) {
                    continue;
                }

                // if the tweets source is facebook ignore
                if (stripos($tweet['source'], $fbsourceurl) !== false) {
                    continue;
                }

                // also do not want retweets
                if ($excludereweets && !empty($tweet['retweeted_status_id'])) {
                    continue;
                }
                // Retweets - I have how Twitter have done this.
                if ($excludereweets && (preg_match('/^RT/', $tweet['full_text']))) {
                    continue;
                }
                /* End Ignores */

                $dbrecord = array();
                $dbrecord['sourcetype'] = $sourcename;
                $dbrecord['isevent'] = 1;
                // Id
                $dbrecord['sourceid'] = $tweet['id'];

                if (!journalRecordExists($dbrecord['sourcetype'], $dbrecord['sourceid'])) {
                    // Tue Jan 07 09:28:49 +0000 2020
                    //   D   M    d   H: i: s  O Y
                    if ($tstweet = DateTime::createFromFormat('D M d H:i:s O Y', $tweet['created_at'])) {
                        $dbrecord['startdate'] = $tstweet->format('Y-m-d');
                        $dbrecord['starttime'] = $tstweet->format('H:i:s');
                        $dbrecord['details'] = $tweet['full_text'];

                        // Save the record by calling saveCalendarRecord($dbrecord)
                        if (saveCalendarRecord($dbrecord)) {
                            $savedcount++;
                        } else {
                            // Complain or something.
                            $errorcount++;
                        }
                    }else{
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
