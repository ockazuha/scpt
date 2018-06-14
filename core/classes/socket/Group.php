<?php
class Group {
    public $socket, $con, $data;
    
    function __construct($socket, $con) {
        $this->socket = $socket;
        $this->con = $con;
    }
}
