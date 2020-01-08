<?php

require_once(__DIR__ . '/locallib.php');

class pluginmanager {

    public $plugintypes;

    public $allplugins;

    /**
     * Get all the installed plugins
     */
    public function __construct() {
        $this->plugintypes = $this->_populate_plugin_types();
        $this->allplugins = array();
    }

    /**
     * Read the subdirectories to determine which plugin types have been installed
     *
     * @return array
     */
    private function _populate_plugin_types() {
        return get_subdirectories(__DIR__);
    }

    /**
     * Return the found plugin types.
     *
     * @return array
     */
    public function get_plugin_types() {
        return $this->plugintypes;
    }

    public function get_allplugins() {
        return $this->allplugins;
    }


    public function get_plugins_by_type($type) {
        if (empty($this->allplugins[$type])) {
            $pluginlib = __DIR__ . "/$type/lib.php";
            if (file_exists($pluginlib)) {
                require_once($pluginlib);
                $classname = $type . "_plugins";
                $plugin = new $classname();
                $this->allplugins[$type] = $plugin->get_plugins();
            }
        }
        return $this->allplugins[$type];
    }

    public function get_plugins_for_select($type) {
        $selectoptions = array();
        $typeplugins = $this->get_plugins_by_type($type);
        foreach ($typeplugins as $pluginclass => $details) {
            $selectoptions[$pluginclass] = $details['pluginname'];
        }
        asort($selectoptions);
        return $selectoptions;
    }

    /**
     * Function to load the plugin so that we can use it.
     *
     * @param string $plugintype
     * @param string $pluginname
     * @return stdClass | bool
     */
    function load_plugin($plugintype, $pluginclass) {
        if (file_exists($this->allplugins[$plugintype][$pluginclass]['classfile'])) {
            require_once($this->allplugins[$plugintype][$pluginclass]['classfile']);
            $plugin = new $pluginclass();
            return $plugin;
        }else{
            return false;
        }
    }

}


abstract class plugins {

    public function get_plugins() {
        return array();
    }

    public function process_file($filedetails) {
        return null;
    }

}