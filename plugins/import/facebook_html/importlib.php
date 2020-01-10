<?php


class import_facebook_html extends import_plugins {

    private $name = 'Facebook HTML Timeline Import';
    private $defaultsourcename = 'FacebookTL';

    public function get_name() {
        return $this->name;
    }

    public function process_file($filedetails, $sourcename = null) {

        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }

        /*
         * for some reason the paragraph markers in this file
         */
        $missrecords = array(' shared ', ' likes ', 'now friends', ' went to ');

        // Is this a HTML file?
        $message = 'Processed upload file - ';
        if ($filedetails['type'] != 'text/html') {
            throw new Exception('Can only process HTML files');
        }

        if (file_exists($filedetails['tmp_name'])) {

            /* file needs to be preprossed as <p> are not working as expected */
            // Preprocessing required because of <p>s
            // sed -i 's|p>|div class="fbrecord">\n|g' timeline.htm
            $filecontents = file_get_contents($filedetails['tmp_name']);
            $pattern = '/p>/';
            $replace = "div class='fbrecord'>\n";
            $filecontents = preg_replace($pattern, $replace, $filecontents);

            $document = new DOMDocument;
            libxml_use_internal_errors(TRUE);

            // if ($document->loadHTMLFile($filedetails['tmp_name'])) {
            if ($document->loadHTML($filecontents)) {
            	libxml_clear_errors();
            	$document->preserveWhiteSpace = false;

            	$xpath = new DOMXpath($document);
             	$entries = $xpath->query("//div[@class='fbrecord']");
            	if (is_null($entries)) {
            		$entries = new DOMNodeList();		//empty
            	}

            }else{
            	throw new Exception('File not loaded');
            }

            if ($entries->length == 0) {
            	//sed -i 's|p>|div class="fbrecord">\n|g' timeline.htm
                throw new Exception('You need to preprocess this file as tags have an issue');
            }

        	$nodectr = 0;
        	$errorcount = 0;
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
        		    'sourcetype' => $sourcename,
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

        				if (!saveCalendarRecord($dbrecord)) {
        				    $errorcount++;
        				}
        			}else{
        			    $errorcount++;
        			}
        		}else{
        		    $errorcount++;
        		}
        		if ($nodectr > 50) {
        		    break;
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
