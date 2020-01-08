<?php

class pluginmanager {

    public $plugintypes;

    /**
     * Get all the installed plugins
     */
    public function __construct() {
        $myplugintypes = $this->get_plugin_types();

        foreach ($myplugintypes as $plugintype => $pluginfile) {
            require_once($pluginfile);

            $this->allplugins[$plugintype] = '';
        }


    }

/*     public function get_plugintypes( {
        if ($type) {
            return $this->allplugins[$type];
        }else{
            return $this->allplugins;
        }
    } */


    protected function get_plugin_types() {
        $plugintypes = array();

        $dhandle = dir(__DIR__);
        if ($dhandle !== false || $dhandle !== null) {
            while (false !== ($entry = readdir($dhandle))) {
                if ($entry != "." && $entry != "..") {
                    if (is_dir($entry)) {
                        $pluginfile = $dhandle->path . '/' . $pluginfilename;
                        if (file_exists($pluginfile)) {
                            $pluginfiles[basename($dhandle->path)] = $pluginfile;
                        }
                    }
                }
            }
            $dhandle->close();
        }
        return $plugintypes;
    }


    /**
     *
     * @param unknown $plugin
     */
    public function get_plugin_name($plugin) {

    }
}


abstract class plugins {


}