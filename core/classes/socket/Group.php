<?php
class Group {
    public $socket, $con;
    
    function __construct($socket, $con) {
        $this->socket = $socket;
        $this->con = $con;
    }
}
