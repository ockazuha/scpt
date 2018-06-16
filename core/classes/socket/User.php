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
                
                if (cfg('socket')['to_jpg']['is_to_jpg']) {
                    $file = FILES_DIR . '/temp_to_jpg/source/' . microtime(true);
                    $file = base64ToFile($file, $data['base64']);
                    $file_jpg = FILES_DIR . '/temp_to_jpg/jpg/' . microtime(true) . '.jpg';
                    $width = cfg('socket')['to_jpg']['width'];
                    $height = cfg('socket')['to_jpg']['height'];
                    $res = imgToJPG($file, $file_jpg, $width, $height, cfg('socket')['to_jpg']['quality']);
                    
                    if ($res === 0) {
                        $data['base64'] = fileToBase64($file_jpg);
                    } else {
                        trigger_error('Error imgToJPG: ' . $res);
                    }
                    
                    if (cfg('socket')['to_jpg']['is_unlink']) {
                        unlink($file);
                        unlink($file_jpg);
                    }
                }
                
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
                        . "url='$data[url]',"
                        . "bid='$data[bid]'");
                
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
