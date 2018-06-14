<?php
class Group {
    public $socket, $con;
    
    function __construct($socket, $con) {
        $this->socket = $socket;
        $this->con = $con;
    }
    
    /*function sendOk($num_request) {
        $this->socket->send($this->con, 'ok', '', false, $num_request);
    }*/
}
