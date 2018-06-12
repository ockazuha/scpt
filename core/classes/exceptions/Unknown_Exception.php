<?php
class Unknown_Exception extends Base_Exception {
    function __construct($e) {
        //тут может нужна будет трассировка, но пока не надо
        parent::__construct("Class: " . get_class($e) . ". " . $e->getCode() . ": " . $e->getMessage());
    }
}
