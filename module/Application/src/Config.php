<?php

class Config {

	private static $instance;

	public $config = array();

    public function __construct() {
        foreach (glob("{module/*/config,config}/autoload/{,*.}{global,local}.php", GLOB_BRACE) as $file) {
            $config = include $file;
            if (is_array($config)) {
                $this->config = merge($this->config, $config);
            }
        }
	}

	public static function get_instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public static function getConfig($key = null, $default = null) {
		$instance = self::get_instance();
		if (!empty($key)) {
			return (!empty($instance->config[$key])?$instance->config[$key]:$default);   
		}
		return $instance->config;
    }

}
