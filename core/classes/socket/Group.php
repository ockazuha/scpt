<?php
class Group {
    public $socket, $con, $data, $buffer = [];
    
    function __construct($socket, $con) {
        $this->socket = $socket;
        $this->con = $con;
    }
}
