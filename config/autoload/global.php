<?php

return array(
	'base_dir' => dirname(dirname(dirname(__FILE__))),
	'view_paths' => glob('{module/*/views/,module/*/views/*,views/*,views/}/', GLOB_BRACE),
	'db_dir' =>  dirname(dirname(dirname(__FILE__))) . '/var/db',
	'log_dir' =>  dirname(dirname(dirname(__FILE__))) . '/var/log',
	'install_dir' => dirname(dirname(dirname(__FILE__))) . '/var/install/sql',
);
