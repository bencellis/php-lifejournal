<?php

require_once('includes/lib.php');

$rssfile = 'datatest/rss/benjyellis.net_blog_feed.xml';

if (($rss = simplexml_load_file($rssfile)) === false) {
	die("failed to load RSS file");
}

if (!isset($rss->channel)) {
	die('Not an RSS file');
}

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

    <div class="container">
	      <div class="row">
	        <div class="col-md-12">
	        	<h1>RSS Import</h1>
	         </div>
	      </div>
	      <div class="row">
	        <div class="col-md-12">
	        	<p>Processing <?php echo $rss->channel->title; ?> Items</p>
	        	<p>RSS Generated by <?php echo $rss->channel->generator; ?></p>
	         </div>
	      </div>
	      <div class="row">
	        <div class="col-md-12">
				<?php
					$itemcount = 0;
					foreach($rss->channel->item as $item) {
						$itemcount++;
						$dbrecord = array(
							'sourcetype' => 'RSSFeed',
							'isevent' => 1
						);

						if (isset($item->guid)) {
							$dbrecord['sourceid'] = $item->guid->__toString();
						}else{
							$dbrecord['sourceid'] = $item->link->__toString();
						}

						if ($dbrecord['sourceid']) {
 							if (journalRecordExists($dbrecord['sourcetype'], $dbrecord['sourceid'])) {
								continue;
							}
						}else{
							echo "<p>No SourceId can be determined for '" . $item->title . "' Entry</p>\n";
						}

						// lets get dates
						if ($postdate = DateTime::createFromFormat(DateTime::RSS, $item->pubDate->__toString())) {
							$dbrecord['startdate'] = $postdate->format('Y-m-d');
							$dbrecord['starttime'] = $postdate->format('H:i:s');

							$dbrecord['details'] = "Posted '" . trim($item->title->__toString()). "\n";
							$dbrecord['details'] .= trim($item->description->__toString()) . "\n";
							$dbrecord['details'] = strip_tags($dbrecord['details']);

							$dbrecord['details'] .= "Link: " . trim($item->link->__toString());

							//echo '<pre>Contents: ' . print_r($dbrecord, true) . '</pre>';
							if (saveCalendarRecord($dbrecord)) {
								echo "<p>Record '" . $dbrecord['sourceid'] . "' successfully saved</p>";
							}else{
								echo "<p>Failed to save record '" . $dbrecord['sourceid'] . "'</p>";
							}

						}else{
							echo "Cannot make Date for " .  $item->pubDate->__toString();
							continue;
						}

/* 						if ($itemcount > 10) {
							break;
						}
 */
					}
				?>
	        </div>
	      </div>
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>
