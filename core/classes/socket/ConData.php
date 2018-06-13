<?php
class ConData {
    public $con, $obj, $group, $username;
    
    function __construct($con, $obj, $group, $username) {
        $this->con = $con;
        $this->obj = $obj;
        $this->group = $group;
        $this->username = $username;
    }
}
