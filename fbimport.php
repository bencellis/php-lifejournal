<?php


/*
 * for some reason the paragraph markers in this file
 */
require_once('includes/lib.php');

$missrecords = array(' shared ', ' likes ', 'now friends');

$document = new DOMDocument;
$tlfile = 'datatest/facebook-bencellis/html/timeline.htm';
libxml_use_internal_errors(TRUE);
if ($document->loadHTMLFile($tlfile)) {
//if ($document->loadHTML(file_get_contents($tlfile))) {
	libxml_clear_errors();
	$document->preserveWhiteSpace = false;

 	$xpath = new DOMXpath($document);

 	$entries = $xpath->query("//div[@class='meta']");
	if (is_null($entries)) {
		$entries = new DOMNodeList();		//empty
	}
}else{
	die('<p>File not loaded</p>');
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

					echo "Comment field is " . $entry->nextSibling->nextSibling->getNodePath() . "<br />";

					echo "Text field reported as " .  $entry->nextSibling->getNodePath() . "<br />";

					//DOMElement
					// if there is a comment - we always save the record - else we see if its a shase
					if (!$details = trim($entry->nextSibling->nextSibling->textContent)) {
						foreach ($missrecords as $missthis) {
							if (stripos(trim($entry->nextSibling->textContent), $missthis) !== false) {
								continue(2);
							}
						}
					}

					if ($adddets = trim($entry->nextSibling->textContent)) {
						$details = $adddets . ' ~ ' . $details;
					}

					$nodectr++;
					$dbrecord = array(
						'sourcetype' => 'Facebook',
						'startdate' => $entry->textContent,
						'details' => $details,
					);

					echo '<pre>Contents: ' . print_r($dbrecord, true) . '</pre>';

					/* 		if ($entry->childNodes->length <> 0) {
					 foreach ($entry->childNodes as $nodechild) {
					 echo '<pre>' . print_r($nodechild, true) . '</pre>';
					 }
						}else{
						echo '<p>This node has no children: ' . get_class($entry) . '</p>';
						} */

					if ($nodectr > 10) {
						break;
					}
				}
				die();

			?>

         </div>
      </div>

    </div> <!-- /container -->
    <hr>

<?php include_once('includes/footer.php'); ?>