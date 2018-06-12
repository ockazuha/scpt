<?php
class PHP_Exception extends Base_Exception {
    function __construct($msg, $code, $file, $line) {
        parent::__construct("$code: $msg in $file on line $line");
    }
}
