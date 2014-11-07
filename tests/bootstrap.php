<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

include_once 'module/Application/src/functions.php';

$loader = require __DIR__ . '/../vendor/autoload.php';

$loader->add('Retext_Tests', __DIR__);

define('TEST_MISC', realpath(__DIR__ . '/misc/'));
