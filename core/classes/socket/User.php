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
                $data['is_reg'] = (bool)$data['is_reg'];
                $data['is_num'] = (bool)$data['is_num'];
                $data['is_phrase'] = (bool)$data['is_phrase'];
                
                if ($data['is_reg'] and $data['is_num']) $data['is_reg'] = false;
                
                $keys = '' . (int)$data['is_reg'] . (int)$data['is_phrase'] . (int)$data['is_num'];
                
                $file_microtime = microtime(true);
                //if (cfg('socket')['to_jpg']['is_to_jpg']) { // не выключать
                $file = FILES_DIR . '/temp_to_jpg/source/' . $file_microtime;
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

                $file_jpg = FILES_DIR . '/temp_to_jpg/jpg/' . $file_microtime . '.jpg';
                $width_jpg = cfg('socket')['to_jpg']['width'];
                $height_jpg = cfg('socket')['to_jpg']['height'];

                try {
                    $res = imgToJPG($file, $file_jpg, $size, [$width_jpg, $height_jpg], cfg('socket')['to_jpg']['quality']);
                } catch (PHP_Exception $e) {
                    $sock->send($this->con, 'skip');
                    MyError::exceptionCatcher($e, false);
                    rename($file, FILES_DIR . '/temp_to_jpg/error_source/' . $file_microtime . '.' . pathinfo($file, PATHINFO_EXTENSION));
                    break;
                }

                if ($res !== 0) {
                    $sock->send($this->con, 'skip');
                    MyError::exceptionCatcher(new Base_Exception('Error imgToJPG: ' . $res), false);
                    break;
                }

                $data['base64'] = fileToBase64($file_jpg);
                $hash = md5($data['base64']) . $width . $height;
                
                if (!($width === 320 and $height === 50)) {
                    $is_two = false;
                } else {
                    $is_two = true;
                    $file_one = FILES_DIR . '/temp_to_jpg/one/' . $file_microtime . '.jpg';
                    $file_two = FILES_DIR . '/temp_to_jpg/two/' . $file_microtime . '.jpg';
                    copy($file_jpg, $file_one);
                    copy($file_jpg, $file_two);
                    
                    crop($file_one, 0, 0, $width_jpg/2, $height_jpg);
                    $base64_one = fileToBase64($file_one);
                    crop($file_two, $width_jpg/2, 0, $width_jpg/2, $height_jpg);
                    $base64_two = fileToBase64($file_two);
                    
                    $base64_one_hash = md5($base64_one);
                    $base64_two_hash = md5($base64_two);
                }
                
                if (cfg('socket')['to_jpg']['is_unlink']) {
                    unlink($file);
                    unlink($file_jpg);
                    if ($is_two) {
                        unlink($file_one);
                        unlink($file_two);
                    }
                }
                //}
                
                $hash = md5($data['base64']) . "$width$height";
                
                if ($is_two) {
                    $hash_one = "$base64_one_hash$width$height";
                    $hash_two = "$base64_two_hash$width$height";
                    
                    $r_query = '';
                    switch ($keys) {
                        case '000':
                            break;
                        case '001':
                            $r_query .= " AND is_num=TRUE";
                            break;
                        case '010':
                            break;
                        case '100':
                            $r_query .= " AND is_reg=TRUE";
                            break;
                        case '011':
                            $r_query .= " AND is_num=TRUE";
                            break;
                    }
                    
                    $r1 = db()->query("SELECT id,is_skip,input,is_phrase2,image_id FROM repeats WHERE hash='$hash_one'" . $r_query)->fetch_assoc();
                    $r2 = db()->query("SELECT id,is_skip,input,is_phrase2,image_id FROM repeats WHERE hash='$hash_two'" . $r_query)->fetch_assoc();
                    
                    if ($r1 and $r2) {
                        db()->query("UPDATE repeats SET count=count+1 WHERE id='$r1[id]'");
                        db()->query("UPDATE repeats SET count=count+1 WHERE id='$r2[id]'");
                        $image_id_one = $r1['image_id'];
                        $image_id_two = $r2['image_id'];
                        
                        if ($r1['is_skip'] and $r2['skip']) {
                            $sock->send($this->con, 'skip');
                            break;
                        } elseif ($r1['is_skip']) {
                            $input = $r2['input'];
                            
                            if ($data['is_phrase']) {
                                if (!$r2['is_phrase2']) {
                                    $input .= ' ';
                                }
                            }
                            
                            $sock->send($this->con, 'input', $input);
                            break;
                        } elseif ($r2['is_skip']) {
                            $input = $r1['input'];
                            
                            if ($data['is_phrase']) {
                                if (!$r1['is_phrase2']) {
                                    $input .= ' ';
                                }
                            }
                            
                            $sock->send($this->con, 'input', $input);
                            break;
                        } else {
                            $sock->send($this->con, 'input', $r1['input'] . ' ' . $r2['input']);
                            break;
                        }
                    } elseif ($r1) {
                        db()->query("UPDATE repeats SET count=count+1 WHERE id='$r1[id]'");
                        $image_id_one = $r1['image_id'];
                        
                        $is_job = true;
                        $job_code = 2;
                        $job_id = $r1['id'];
                    } elseif ($r2) {
                        db()->query("UPDATE repeats SET count=count+1 WHERE id='$r2[id]'");
                        $image_id_two = $r2['image_id'];
                        
                        $is_job = true;
                        $job_code = 1;
                        $job_id = $r2['id'];
                    }
                } else {
                    $r_query = '';
                    switch ($keys) {
                        case '000':
                            break;
                        case '001':
                            $r_query .= " AND is_num=TRUE";
                            break;
                        case '010':
                            break;
                        case '100':
                            $r_query .= " AND is_reg=TRUE";
                            break;
                        case '011':
                            $r_query .= " AND is_num=TRUE";
                            break;
                    }
                    
                    $r = db()->query("SELECT id,is_skip,input,is_phrase2,image_id FROM repeats WHERE hash='$hash'" . $r_query)->fetch_assoc();
                    
                    if ($r) {
                        db()->query("UPDATE repeats SET count=count+1 WHERE id='$r[id]'");
                        $image_id = $r['image_id'];
                        if ($r['is_skip']) {
                            $sock->send($this->con, 'skip');
                        } else {
                            $input = $r['input'];
                            
                            if ($data['is_phrase']) {
                                if (!$r['is_phrase2']) {
                                    $input .= ' ';
                                }
                            }
                            
                            $sock->send($this->con, 'input', $input);
                        }
                        break;
                    }
                }
                
                if (!isset($image_id)) {
                    db()->query("INSERT INTO images SET base64='" . db()->escape_string($data['base64']) . "'");
                    $image_id = db()->insert_id;
                }
                
                if ($is_two) {
                    if (!isset($image_id_one)) {
                        db()->query("INSERT INTO images SET base64='" . db()->escape_string($base64_one) . "'");
                        $image_id_one = db()->insert_id;
                    }
                    if (!isset($image_id_two)) {
                        db()->query("INSERT INTO images SET base64='" . db()->escape_string($base64_two) . "'");
                        $image_id_two = db()->insert_id;
                    }
                }
                
                if ($is_two) {
                    $query_add = "INSERT INTO captchas SET "
                            . "image_id='$image_id',"
                            . "ts_add='$data[ts_add]',"
                            . "num_user='" . $this->data->username . "',"
                            . "is_reg='$data[is_reg]',"
                            . "is_num='$data[is_num]',"
                            . "is_phrase='$data[is_phrase]',"
                            . "url='$data[url]',"
                            . "bid='$data[bid]',"
                            . "is_caps='$is_caps'" . ($is_caps ? ", id_caps='$id_caps'" : '') . ","
                            . "width='$width',"
                            . "height='$height',"
                            . "mime_type='$mime',"
                            . "hash='$hash',"
                            . "hash_one='$hash_one',"
                            . "hash_two='$hash_two',"
                            . "is_two='$is_two',"
                            . "image_id_one='$image_id_one',"
                            . "image_id_two='$image_id_two'";
                    
                    if (!empty($is_job)) {
                        $query_add .= ",is_job=TRUE,"
                                . "job_code='$job_code',"
                                . "job_id='$job_id'";
                    }
                } else {
                    $query_add = "INSERT INTO captchas SET "
                            . "image_id='$image_id',"
                            . "ts_add='$data[ts_add]',"
                            . "num_user='" . $this->data->username . "',"
                            . "is_reg='$data[is_reg]',"
                            . "is_num='$data[is_num]',"
                            . "is_phrase='$data[is_phrase]',"
                            . "url='$data[url]',"
                            . "bid='$data[bid]',"
                            . "is_caps='$is_caps'" . ($is_caps ? ", id_caps='$id_caps'" : '') . ","
                            . "width='$width',"
                            . "height='$height',"
                            . "mime_type='$mime',"
                            . "hash='$hash'";
                }
                
                db()->query($query_add);
                $captcha_id = db()->insert_id;
                
                $data['id'] = $captcha_id;
                $data['num_user'] = $this->data->username;
                $data['is_caps'] = false;
                $data['is_two'] = $is_two;
                
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
