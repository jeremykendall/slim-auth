<?php

error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('America/Chicago');

define('APPLICATION_PATH', realpath(__DIR__ . '/..'));

$loader = require APPLICATION_PATH . '/vendor/autoload.php';
$loader->add('JeremyKendall\\Slim\\Auth\\Tests\\', APPLICATION_PATH . '/tests');

define('SLIM_MODE', 'testing');

function d($var) {
    var_dump($var);
}

function dd($var) {
    d($var);
    die();
}
