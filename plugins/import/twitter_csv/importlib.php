<?php

class import_twitter_csv extends import_plugins {

    private $name = 'Twitter CSV Tweets Import';
    private $defaultsourcename = 'Twitter';

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

            // $tweetfile = 'datatest/midstweets.csv';
            // $tweetfile = 'datatest/bencellistweets.csv';

            $tweets = file($filedetails['tmp_name']);

            $columns = array();
            $fbsourceurl = 'www.facebook.com/twitter';
            $excludereweets = true;

            /*Array
            (
            		[0] => tweet_id
            		[1] => in_reply_to_status_id
            		[2] => in_reply_to_user_id
            		[3] => timestamp
            		[4] => source
            		[5] => text
            		[6] => retweeted_status_id
            		[7] => retweeted_status_user_id
            		[8] => retweeted_status_timestamp
            		[9] => expanded_urls
            )*/

    		$linecnt = 0;
    		$incompleteline = '';
    		$errorcount = 0;
    		foreach ($tweets as $tweet) {
    			$linecnt++;

    			$dbrecord = array(
    			    'sourcetype' => $sourcename,
    				'isevent' => 1
    			);

    			//check for a incomplete line
    			if ($linecnt > 1 ) {
    				if (preg_match('/^"/', $tweet) === 0  && $incompleteline) {
    					$tweet = $incompleteline . ' ~ ' . $tweet;
    					$incompleteline = '';
    				}
    			}

    			$row = str_getcsv($tweet);
    			/*
    			 * Determine if we have line breaks or incomplete lines.
    			 */
    			if ($linecnt == 1) {
    				$columns = $row;
    				continue;
    			}

    			if (count($row) !== count($columns)) {
    				$incompleteline = trim($tweet);
    				continue;
    			}
    			/* end incomplete lines */

    			// Make the row to an array
    			$rawrecord = array();
    			foreach ($columns as $index => $value) {
    				$rawrecord[$value] = $row[$index];
    			}

    			// if the tweet is in reply to someone - ignore
    			if ($rawrecord['in_reply_to_status_id'] || 	$rawrecord['in_reply_to_user_id']) {
    				continue;
    			}
    			// if the tweets source is facebook ignore
    			if (strpos($rawrecord['source'], $fbsourceurl) !== false) {
    				continue;
    			}
    			// also do not want retweets
    			if ($excludereweets && $rawrecord['retweeted_status_id']) {
    				continue;
    			}

    			// uid
    			$dbrecord['sourceid'] = $rawrecord['tweet_id'];

    			if (!journalRecordExists($dbrecord['sourcetype'], $dbrecord['sourceid'])) {
    				// let's deal with the time - 2017-03-10 13:48:52 +0000
    				if ($tstweet = DateTime::createFromFormat('Y-m-d H:i:s O', $rawrecord['timestamp'])) {
    					//$dbrecord['rawtimestamp'] = $rawrecord['timestamp'];		// delete this at some point
    					$dbrecord['startdate'] = $tstweet->format('Y/m/d');
    					$dbrecord['starttime'] = $tstweet->format('H:i:s');
    					$dbrecord['details'] = $rawrecord['text'];
    					$dbrecord['details'] = str_replace(' ~ ', "\n", $rawrecord['text']);

    					//lets add the full URLS if neccessary
    					if ($rawrecord['expanded_urls']) {
    						$dbrecord['details'] .= "\nURLS\n" . str_replace(',', "\n", $rawrecord['expanded_urls']);
    					}

    					if (!saveCalendarRecord($dbrecord)) {
    					    $errorcount++;
    					}
    				}else{
    				    $errorcount++;
    				}
    			}
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