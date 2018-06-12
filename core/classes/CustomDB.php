<?php
class CustomDB extends mysqli {
    function __construct($auth_data) {
        parent::__construct($auth_data['host'], $auth_data['user'], $auth_data['pass'], $auth_data['base_name']);
        $this->set_charset('utf8');
    }
    
    function query($query_str) {
        $result = parent::query($query_str);
        if ($this->errno !== 0) {
            if (is_array($result)) {
                $result = print_r($result, true);
            } elseif (is_bool($result)) {
                $result = '(bool)' . (int)$result;
            }
            throw new CustomDB_Exception($this, $query_str, $result);
        }
        return $result;
    }
}
