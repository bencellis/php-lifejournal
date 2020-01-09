<?php

require_once('includes/lib.php');

$tweetfile = 'datatest/midstweets.csv';
//$tweetfile = 'datatest/bencellistweets.csv';
$tweets = file($tweetfile);
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

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

    <div class="container">
      <div class="row">
        <div class="col-md-12">
        	<p>Processing Twitter CSV file</p>
         </div>
      </div>
      <div class="row">
        <div class="col-md-12">
    		<?php
    			$linecnt = 0;
    			$incompleteline = '';
    			foreach ($tweets as $tweet) {
    				$linecnt++;
    				echo "Processing Line $linecnt<br/>";

    				$dbrecord = array(
    					'sourcetype' => 'Twitter',
    					'isevent' => 1
    				);

    				//check for a incomplete line
    				if ($linecnt > 1 ) {
	    				if (preg_match('/^"/', $tweet) === 0  && $incompleteline) {
	    					echo "<p>Next Line being added to incomplete line<p>\n";
	    					$tweet = $incompleteline . ' ~ ' . $tweet;
	    					$incompleteline = '';
	    				}
    				}

    				$row = str_getcsv($tweet);
    				if ($linecnt == 1) {
    					$columns = $row;
    					continue;
    				}

    				if (count($row) !== count($columns)) {
    					echo "<p>This line contains a return in one of the fields, attempting recovering of incomplete Line</p>";
    					$incompleteline = trim($tweet);
    					continue;
    				}

    				// may the row to an array
    				$rawrecord = array();
    				foreach ($columns as $index => $value) {
    					$rawrecord[$value] = $row[$index];
    				}

					// if the tweet is in reply to someone - ignore
					if ($rawrecord['in_reply_to_status_id'] || 	$rawrecord['in_reply_to_user_id']) {
						echo "<p>No Process: Reply</p>";
						continue;
					}
    				// if the tweets source is facebook ignore
					if (strpos($rawrecord['source'], $fbsourceurl) !== false) {
						echo "<p>No Process: Facebook</p>";
						continue;
					}
					// also do not want retweets
					if ($excludereweets && $rawrecord['retweeted_status_id']) {
						echo "<p>No Process: Retweet</p>";
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

							if (saveCalendarRecord($dbrecord)) {
								echo "<p>Record '" . $dbrecord['sourceid'] . "' successfully saved</p>";
							}else{
								echo "<p>Failed to save record '" . $dbrecord['sourceid'] . "'</p>";
							}

						}else{
							echo '<p>Date Format no good for ' . $rawrecord['timestamp'] . "</p>\n";
						}
					}else{
						echo "<p>Record '" . $dbrecord['sourceid'] . "' already exists</p>";
					}

    			}
    		?>
         </div>
      </div>


    </div> <!-- /container -->
    <hr>

<?php include_once('includes/footer.php'); ?>