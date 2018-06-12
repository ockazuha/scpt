<?php
class CustomDB_Exception extends Base_Exception {
    function __construct($obj, $query_str, $result_str) {
        parent::__construct("$obj->errno: $obj->error\n>>> Query:\n$query_str\n>>> Result:\n$result_str");
    }
}
