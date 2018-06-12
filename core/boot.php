<?php
require_once __DIR__ . '/static_boot.php';
require_once __DIR__ . '/classes/CustomDB.php';
require_once __DIR__ . '/classes/exceptions/CustomDB_Exception.php';

varToFunc('db', new CustomDB(cfg('db')));
