<?php

/**
 * General Library functions for plugins.
 */

function get_subdirectories($dirname, $fullpath = false) {
    $subdirectories = array();

    $dhandle = dir($dirname);
    $dpath = $dhandle->path;
    if ($dhandle !== false && $dhandle !== null) {
        while (false !== ($entry = $dhandle->read())) {
            if ($entry != "." && $entry != "..") {
                if (is_dir("$dpath/$entry")) {
                    if ($fullpath) {
                        $subdirectories[] = "$dpath/$entry";
                    } else {
                        $subdirectories[] = basename($entry);
                    }
                }
            }
        }
        $dhandle->close();
    }
    return $subdirectories;
}

function getJSONerror($lasterror) {
    switch ($lasterror) {
        case JSON_ERROR_NONE:
            $errormsg = 'No errors';
            break;
        case JSON_ERROR_DEPTH:
            $errormsg = 'Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $errormsg = 'Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $errormsg = 'Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            $errormsg = 'Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            $errormsg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            $errormsg = 'Unknown error';
            break;
    }
    return $errormsg;

}