<?php

class import_rss extends import_plugins {

    private $name = 'RSS Feed XML Import';
    private $defaultsourcename = 'RSSFeed';

    public function get_name() {
        return $this->name;
    }

    public function process_file($filedetails, $sourcename = null) {

        if (!$sourcename) {
            $sourcename = $this->defaultsourcename;
        }

        // Is this a RSS file?
        $message = 'Processed upload file - ';
        if ($filedetails['type'] != 'text/xml') {
            throw new Exception('Can only process RSS XML files');
        }

        if (file_exists($filedetails['tmp_name'])) {

            if (($rss = simplexml_load_file($filedetails['tmp_name'])) === false) {
            	throw new Exception("Failed to load RSS file");
            }

            if (!isset($rss->channel)) {
                throw new Exception('Not an RSS file');
            }

    		$itemcount = 0;
    		$errorcount = 0;
    		foreach($rss->channel->item as $item) {
    			$itemcount++;
    			$dbrecord = array(
    			    'sourcetype' => $sourcename,
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
    			}

    			// lets get dates
    			if ($postdate = DateTime::createFromFormat(DateTime::RSS, $item->pubDate->__toString())) {
    				$dbrecord['startdate'] = $postdate->format('Y-m-d');
    				$dbrecord['starttime'] = $postdate->format('H:i:s');

    				$dbrecord['details'] = "Posted '" . trim($item->title->__toString()). "\n";
    				$dbrecord['details'] .= trim($item->description->__toString()) . "\n";
    				$dbrecord['details'] = strip_tags($dbrecord['details']);
    				$dbrecord['details'] .= "Link: " . trim($item->link->__toString());

    				if (!saveCalendarRecord($dbrecord)) {
    				    $errorcount++;
    				}
    			}else{
                    $errorcount++;
    				continue;
    			}

    		}
			if($errorcount) {
			    $message .= "($errorcount Error lines)";
			}else{
			    $message .= ' No errors encountered';
			}
		} else {
		    throw new Exception('Uploaded file is missing.');
		}
		return $message;
	}

}
