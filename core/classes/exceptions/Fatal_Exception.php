<?php
class Fatal_Exception extends PHP_Exception {
    function __construct($error) {
        parent::__construct($error['message'], $error['type'], $error['file'], $error['line']);
    }
}
