<?php

include_once 'module/Application/src/functions.php';

$loader = include 'vendor/autoload.php';

$config = array('events' => array(), 'routes' => array());

$config['database'] = array('type' => '');

foreach (glob("config/autoload/events{,*.}{global,local}.php", GLOB_BRACE) as $file) {
	$event = include $file;
}

foreach (glob("{module/*/config,config}/autoload/routes/{,*.}{global,local}.php", GLOB_BRACE) as $file) {
    $routes = include $file;
    if (is_array($config)) {
        $config['routes'] = merge($config['routes'], $routes);
    }
}
if (getenv('DATABASE_TYPE') && strtolower(getenv('DATABASE_TYPE')) == 'mysql') {
	$config['database']['type'] = 'mysql';
}

return $config;
