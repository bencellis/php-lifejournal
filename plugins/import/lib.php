<?php


class import_plugins extends plugins {

    private $pluginfilename = 'importlib.php';

    private $pluginpath = __DIR__;

    private $ourplugins;

    public function __construct() {
        $this->ourplugins = array();
    }

    public function get_plugins() {
        $paths = get_subdirectories($this->pluginpath, true);
        foreach ($paths as $path) {
            $pluginfile = $path . '/' . $this->pluginfilename;
            if (file_exists($pluginfile)) {
                // TODO remove this
                if (basename($path) == 'csv') {
                    require_once($pluginfile);
                    $classname = 'import_' . basename($path);
                    $plugin = new $classname();
                    if ($pluginname = $plugin->get_name()) {
                        $this->ourplugins[$classname] = array(
                            'pluginname' => $pluginname,
                            'classfile' => $pluginfile
                        );
                    }
                }
            }
        }
        return $this->ourplugins;
    }



}
