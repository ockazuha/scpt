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
                
                if (cfg('socket')['to_jpg']['is_to_jpg']) { // не выключать
                    $file = FILES_DIR . '/temp_to_jpg/source/' . microtime(true);
                    $file = base64ToFile($file, $data['base64']);
                    $mime = $file[1];
                    $file = $file[0];
                    $size = getimagesize($file);
                    $width = $size[0];
                    $height = $size[1];
                    
                    $is_caps = db()->query("SELECT id FROM caps WHERE width='$width' AND height='$height' AND mime_type='$mime'")->fetch_assoc();
                    
                    if ($is_caps) {
                        $id_caps = $is_caps['id'];
                        $is_caps = true;
                        $data['is_reg'] = false;
                    }
                    
                    $file_jpg = FILES_DIR . '/temp_to_jpg/jpg/' . microtime(true) . '.jpg';
                    $width_jpg = cfg('socket')['to_jpg']['width'];
                    $height_jpg = cfg('socket')['to_jpg']['height'];
                    
                    try {
                        $res = imgToJPG($file, $file_jpg, $width_jpg, $height_jpg, cfg('socket')['to_jpg']['quality']);
                    } catch (PHP_Exception $e) {
                        $sock->send($this->con, 'skip');
                        MyError::exceptionCatcher($e, false);
                        rename($file, FILES_DIR . '/temp_to_jpg/error_source/' . microtime(true) . '.' . pathinfo($file, PATHINFO_EXTENSION));
                        break;
                    }
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
                        . "bid='$data[bid]',"
                        . "width='$width',"
                        . "height='$height',"
                        . "mime_type='$mime',"
                        . "is_caps='$is_caps'" . ($is_caps ? ", id_caps='$id_caps'" : '')
                        . "");
                
                $captcha_id = db()->insert_id;
                
                $data['id'] = $captcha_id;
                $data['num_user'] = $this->data->username;
                $data['is_caps'] = false;
                if ($is_caps) {
                    $data['is_caps'] = true;
                    $data['id_caps'] = $id_caps;
                }
                
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
