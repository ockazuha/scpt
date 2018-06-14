<?php
class User extends Group {
    function messageHandler($cmd, $data) {
        $sock = $this->socket;
        
        switch ($cmd) {
            case 'get_user':
                $res = db()->query("SELECT login,pass,is_display,is_pause FROM users WHERE num_user='" . $this->data->username . "'")->fetch_assoc();
                $sock->send($this->con, 'user', jsonEncode($res));
                break;
        }
    }
}
