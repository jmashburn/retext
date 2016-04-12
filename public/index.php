<?php

ini_set('display_errors', 1);
ini_set('xdebug.profiler_enable', 1);

$time = microtime();
$time = explode(' ', $time);
$start = $time[1] + $time[0];

if (getenv('SESSION_PATH')) {
	session_save_path(getenv('SESSION_PATH'));
}
session_start();
$config = include_once 'init_autoloader.php';


ToroHook::add('after_request', function() use ($start) {
    $time = microtime();
    $time = explode(' ', $time);
    $finish = $time[1] + $time[0];
    $total_time = round(($finish - $start), 4);
    Application\Log::debug("Page Generated in " . $total_time . " seconds");
    #echo "<div><strong>Page Generated in " . $total_time . " seconds.</strong></div>";
});

ToroHook::add("404", function($params) {
	echo "404 Not Found";
});

Toro::serve($config['routes']);
