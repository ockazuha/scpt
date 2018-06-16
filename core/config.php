<?php
define('TS_START', microtime(true));
define('VER', TS_START);
define('FILES_DIR', __DIR__ . '/../files');

$cfg = [
    'error_reporting' => -1,
    'display_errors' => false,
    'log_errors' => true,
    'error_log' => FILES_DIR . '/logs/main.log',
    'date.timezone' => 'Etc/GMT-4', // - это +
    'is_call_500error' => true,
    'is_var_dump_trace' => false,
    'time_limit' => 30,
    'domain' => 'scpt.ru',
    
    'db' => [
        'is_create_tables' => true,
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'base_name' => 'scpt'
    ],
    
    'socket' => [
        'server_addr' => 'tcp://0.0.0.0:8000',
        'client_addr' => 'ws://127.0.0.1:8000',
        'is_log_server' => false,
        'is_log_client' => false,
        'buffer_size' => 6500,
        'timeout_check' => 1000//250
    ],
    
    'client' => [
        'max_time' => 30,
        'is_log' => false,
    ],
    
    'userscript' => [
        't_cpt' => 250,//50,
        'max_time' => 32,
        't_check_skip' => 250,//50,
        'is_log' => false,
        'max_wait_time' => 35000,
        't_check_stop_cpt' => 250//50
    ]
];
