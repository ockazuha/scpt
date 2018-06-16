<?php
class User extends Group {
    function messageHandler($cmd, $data) {
        $sock = $this->socket;
        
        switch ($cmd) {
            case 'get_user':
                $res = db()->query("SELECT login,pass,is_display,is_pause FROM users WHERE num_user='" . $this->data->username . "'")->fetch_assoc();
                $sock->send($this->con, 'user', jsonEncode($res));
                break;
            case 'capt':
                $data = json_decode($data, true);
                
                $base64 = db()->escape_string($data['base64']);
                
                db()->query("INSERT INTO images SET "
                        . "base64='$base64'");
                $image_id = db()->insert_id;
                
                db()->query("INSERT INTO captchas SET "
                        . "image_id='$image_id',"
                        . "ts_add='$data[ts_add]',"
                        . "num_user='" . $this->data->username . "',"
                        . "is_reg='$data[is_reg]',"
                        . "is_num='$data[is_num]',"
                        . "is_phrase='$data[is_phrase]',"
                        . "url='$data[url]'");
                
                $captcha_id = db()->insert_id;
                
                $data['id'] = $captcha_id;
                $data['num_user'] = $this->data->username;
                
                $sock->sendClient('capt', $data, true);
                break;
            case 'curr_discount':
                $sock->sendClient('curr_discount', [$this->data->username, $data], true);
                break;
            case 'stat':
                $sock->sendClient('stat', $data);
                break;
        }
    }
}
