<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/classes/MyError.php';
require_once __DIR__ . '/classes/exceptions/Base_Exception.php';
require_once __DIR__ . '/classes/exceptions/PHP_Exception.php';
require_once __DIR__ . '/classes/exceptions/Fatal_Exception.php';
require_once __DIR__ . '/classes/exceptions/Unknown_Exception.php';

varToFunc('cfg', $cfg);

header('Access-Control-Allow-Origin: *');
error_reporting(cfg('error_reporting'));
ini_set('display_errors', cfg('display_errors'));
ini_set('log_errors', cfg('log_errors'));
ini_set('error_log', cfg('error_log'));
ini_set('date.timezone', cfg('date.timezone'));

if (!defined('OFF_ERROR_HANDLING')) {
    set_error_handler(['MyError', 'errorCatcher']);
    set_exception_handler(['MyError', 'exceptionCatcher']);
    register_shutdown_function(['MyError', 'shutdown']);
}

ob_start();