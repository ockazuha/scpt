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
                $keys = $this->normalizeKeysAndGenStrKeys($data['is_reg'], $data['is_phrase'], $data['is_num']);
                $file_microtime = microtime(true);
                $is_to_jpg = cfg('socket')['to_jpg']['is_to_jpg'];
                
                $file = FILES_DIR . '/temp_to_jpg/source/' . $file_microtime;
                $file = base64ToFile($file, $data['base64']);
                $mime = $file[1];
                $file = $file[0];
                $size = getimagesize($file);
                $width = $size[0];
                $height = $size[1];
                
                if ($is_to_jpg) {
                    try {
                        extract($this->imgToJPG($file_microtime, $file, $size));
                    } catch (Base_Exception $e) {
                        $sock->send($this->con, 'skip');
                        MyError::exceptionCatcher($e, false);
                        break;
                    }
                    
                    $data['base64'] = fileToBase64($file_jpg);
                } else {
                    $file_jpg = $file;
                    $width_jpg = $width;
                    $height_jpg = $height;
                }
                
                if (!($width === 320 and $height === 50)) {
                    $is_two = false;
                } else {
                    $is_two = true;
                    extract($this->parseTwo($file_microtime, $file_jpg, $width_jpg, $height_jpg));
                }
                
                if (cfg('socket')['to_jpg']['is_unlink']) {
                    unlink($file);
                    if ($is_to_jpg) {
                        unlink($file_jpg);
                    }

                    if ($is_two) {
                        unlink($file_one);
                        unlink($file_two);
                    }
                }
                
                $hash = md5($data['base64']) . "{$width}{$height}";
                
                $is_caps = db()->query("SELECT id FROM caps WHERE width='$width' AND height='$height' AND mime_type='$mime'")->fetch_assoc();

                if ($is_caps) {
                    $id_caps = $is_caps['id'];
                    $is_caps = true;
                }
                
                $hash = md5($data['base64']) . "{$width}{$height}";
                
                if ($is_two) {
                    $hash_one = "{$base64_one_hash}{$width}{$height}";
                    $hash_two = "{$base64_two_hash}{$width}{$height}";
                    
                    $r1 = $this->getRepeat($hash_one, $keys);
                    $r2 = $this->getRepeat($hash_two, $keys);
                    
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
                    $r = $this->getRepeat($hash, $keys);
                    
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
                
                if (!isset($is_job)) $is_job = false;
                
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
                $data['is_two'] = $is_two;
                
                if ($is_caps) {
                    $data['is_caps'] = true;
                    $data['id_caps'] = $id_caps;
                } else {
                    $data['is_caps'] = false;
                }
                
                if ($is_job) {
                    if ($job_code === 1) {
                        $data['base64'] = $base64_one;
                    } elseif ($job_code === 2) {
                        $data['base64'] = $base64_two;
                    }
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
    
    function getRepeat($hash, $keys) {
        $r_query = '';
        switch ($keys) {
            case '001':
                $r_query .= " AND is_num=TRUE";
                break;
            case '100':
                $r_query .= " AND is_reg=TRUE";
                break;
            case '011':
                $r_query .= " AND is_num=TRUE";
                break;
        }
        
        return db()->query("SELECT id,is_skip,input,is_phrase2,image_id FROM repeats WHERE hash='$hash'" . $r_query)->fetch_assoc();
    }
    
    function normalizeKeysAndGenStrKeys(&$is_reg, &$is_phrase, &$is_num) {
        $is_reg = (bool)$is_reg;
        $is_num = (bool)$is_num;
        $is_phrase = (bool)$is_phrase;

        if ($is_reg and $is_num) {
            $is_reg = false;
        }

        return (int)$is_reg . (int)$is_phrase . (int)$is_num;
    }
    
    function imgToJPG($file_microtime, $file, $size) {
        $file_jpg = FILES_DIR . '/temp_to_jpg/jpg/' . $file_microtime . '.jpg';
        $width_jpg = cfg('socket')['to_jpg']['width'];
        $height_jpg = cfg('socket')['to_jpg']['height'];

        try {
            $res = imgToJPG($file, $file_jpg, $size, [$width_jpg, $height_jpg], cfg('socket')['to_jpg']['quality']);
        } catch (PHP_Exception $e) {
            rename($file, FILES_DIR . '/temp_to_jpg/error_source/' . $file_microtime . '.' . pathinfo($file, PATHINFO_EXTENSION));
            throw $e;
        }

        if ($res !== 0) {
            throw new Base_Exception('Error imgToJPG: ' . $res);
        }
        
        return [
            'file_jpg' => $file_jpg,
            'width_jpg' => $width_jpg,
            'height_jpg' => $height_jpg
        ];
    }
    
    function parseTwo($file_microtime, $file, $width, $height) {
        $file_one = FILES_DIR . '/temp_to_jpg/one/' . $file_microtime . '.jpg';
        $file_two = FILES_DIR . '/temp_to_jpg/two/' . $file_microtime . '.jpg';
        copy($file, $file_one);
        copy($file, $file_two);

        crop($file_one, 0, 0, $width/2, $height);
        $base64_one = fileToBase64($file_one);
        crop($file_two, $width/2, 0, $width/2, $height);
        $base64_two = fileToBase64($file_two);

        $base64_one_hash = md5($base64_one);
        $base64_two_hash = md5($base64_two);
        
        return [
            'file_one' => $file_one,
            'file_two' => $file_two,
            'base64_one' => $base64_one,
            'base64_two' => $base64_two,
            'base64_one_hash' => $base64_one_hash,
            'base64_two_hash' => $base64_two_hash
        ];
    }
}
