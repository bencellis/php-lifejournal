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
