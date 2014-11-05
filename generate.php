<?php 

if (!function_exists('getallheaders')) { 
    function getallheaders() { 
		$headers = ''; 
		foreach ($_SERVER as $name => $value)  { 
			if (substr($name, 0, 5) == 'HTTP_')  { 
			   $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
			} 
		} 
		return $headers; 
    } 
} 


function generateRequestSignature($host, $path, $timestamp, $userAgent, $apiKey) {
    $data = $host .":" . $path . ":" . $userAgent . ":" . gmdate('D, d M y H:i:s ', $timestamp) .'GMT';
    return hash_hmac('sha256', $data, $apiKey);
}


$headers = getallheaders();
$path = empty($_GET['path'])?'/':$_GET['path'];
if (empty($_GET['key'])) die('No API Key');
$apiKey = $_GET['key'];
$user = !empty($_GET['username'])?$_GET['username']:'';

$timestamp = !empty($_GET['timestamp'])?strtotime($_GET['timestamp']):time();

echo $user .";". generateRequestSignature($headers['Host'], $path, time(), $headers['User-Agent'], $apiKey) .";" . $timestamp;

