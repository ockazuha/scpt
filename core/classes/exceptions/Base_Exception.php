<?php
class Base_Exception extends Exception {
    private $class;
    
    function __construct($msg, $code = 0) {
        $this->class = get_class($this);
        parent::__construct($msg, $code);
    }
    
    function getClass() {
        return $this->class;
    }
}
