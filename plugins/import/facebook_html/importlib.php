<?php


class import_facebook_html extends import_plugins {
/*
 * for some reason the paragraph markers in this file
 */
require_once('includes/lib.php');

$name = 'Benjamin Ellis';
$missrecords = array(' shared ', ' likes ', 'now friends', ' went to ');

$document = new DOMDocument;
$tlfile = 'datatest/facebook-bencellis/html/timeline.htm';
libxml_use_internal_errors(TRUE);

/* file needs to be preprossed as <p> are not working as expected */

if ($document->loadHTMLFile($tlfile)) {
//if ($document->loadHTML(file_get_contents($tlfile))) {
	libxml_clear_errors();
	$document->preserveWhiteSpace = false;

	$xpath = new DOMXpath($document);
 	$entries = $xpath->query("//div[@class='fbrecord']");
	if (is_null($entries)) {
		$entries = new DOMNodeList();		//empty
	}

}else{
	die('<p>File not loaded</p>');
}

if ($entries->length == 0) {
	//sed -i 's|p>|div class="fbrecord">\n|g' timeline.htm
	die('You need to preprocess this file as <p> tags have an issue');
}

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

    <div class="container">
      <div class="row">
        <div class="col-md-12">
        	<p>Processing Facebook <?php echo $entries->length; ?> timeline items</p>
         </div>
      </div>
      <div class="row">
        <div class="col-md-12">
			<?php
				$nodectr = 0;
				foreach ($entries as $entry) {
					$rawrecord = array(
						'comment' => '',		// can be empty - but not both text and
						'text' => '',			// can be empty - FB action status (shared/likes/ etc) OR
						'meta' => ''
					);

					foreach ($entry->childNodes as $child) {
						if ($child->nodeName == 'div') {
							$rawrecord[$child->getAttribute('class')] = trim($child->textContent);
						}else if ($child->nodeName == '#text'){
							if ($rawrecord['text']) {
								$rawrecord['text'] .= ' ';
							}
							$rawrecord['text'] .= trim($child->textContent);
						}
					}

					//empty records are ignores
					if (!$rawrecord['comment'] && !$rawrecord['text']) {
						continue;
					}

					// if there is a comment - we always save the record - else we see if its a record to miss
					if (!$rawrecord['comment']) {
						foreach ($missrecords as $missthis) {
							if (stripos($rawrecord['text'], $missthis) !== false) {
								continue(2);
							}
						}
					}

					$nodectr++;

					$dbrecord = array(
						'sourcetype' => 'FacebookTL',
						'isevent' => 1
					);

					// date format Wednesday, 1 March 2017 at 12:59 UTC
					$rawrecord['meta'] = str_replace('at ', '', $rawrecord['meta']);
					if ($thisdate = DateTime::createFromFormat('l, j F Y H:i e', $rawrecord['meta'])) {
						$dbrecord['sourceid'] = $thisdate->getTimestamp();
						if (!journalRecordExists($dbrecord['sourcetype'], $dbrecord['sourceid'])) {
							$dbrecord['startdate'] = $thisdate->format('Y/m/d');
							$dbrecord['starttime'] = $thisdate->format('H:i:s');

							if ($rawrecord['text'] && $rawrecord['comment']) {
								$dbrecord['details'] = $rawrecord['text'] . ' ~ ' . $rawrecord['comment'];
							}else if($rawrecord['text']) {
								$dbrecord['details'] = $rawrecord['text'];
							}else{
								$dbrecord['details'] = $rawrecord['comment'];
							}

							//echo '<pre>Contents: ' . print_r($dbrecord, true) . '</pre>';
							if (saveCalendarRecord($dbrecord)) {
								echo "<p>Record '" . $dbrecord['sourceid'] . "' successfully saved</p>";
							}else{
								echo "<p>Failed to save record '" . $dbrecord['sourceid'] . "'</p>";
							}
						}else{
							echo "<p>Record '" . $dbrecord['sourceid'] . "' already exists</p>";
						}
					}else{
						echo "<p>Failed to make a date for '".  $rawrecord['meta'] . "'</p>\n";
					}

/* 					if ($nodectr > 50) {
						break;
					} */
				}
			?>
         </div>
      </div>

    </div> <!-- /container -->
    <hr>

<?php include_once('includes/footer.php'); ?>