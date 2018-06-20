<?php
class Client extends Group {
    function messageHandler($cmd, $data) {
        $sock = $this->socket;
        
        switch ($cmd) {
            case 'exit':
                exit();
                break;
            case 'get_users':
                $this->cmdSendUsers();
                break;
            case 'set_status':
                $data = json_decode($data);
                $type = $data[1];
                $num_user = $data[0];
                
                $status = !(db()->query("SELECT $type FROM users WHERE num_user='$num_user'")->fetch_assoc()[$type]);
                
                db()->query("UPDATE users SET $type='$status' WHERE num_user='$num_user'");
                $sock->sendUser($num_user, 'set_status', [$type, $status], true);
                $this->cmdSendUsers();
                break;
            case 'set_status_all':
                $data = json_decode($data);
                db()->query("UPDATE users SET $data[0]='$data[1]'");
                $sock->sendUsers('set_status', [$data[0], $data[1]], true);
                $this->cmdSendUsers();
                break;
            case 'input':
                $data = json_decode($data, true);
                
                if ($data['is_caps']) {//is_caps
                    db()->query("UPDATE caps SET count=count+1 WHERE id='$data[id_caps]'");
                    $data['input'] = mb_strtoupper($data['input']);
                }
                
                $input = $data['input'];
                
                //is num repeats
                if (db()->query("SELECT value FROM settings WHERE name='is_save_repeats'")->fetch_assoc()['value']) {
                    $r_input = trim($input);
                    
                    $c = db()->query("SELECT * FROM captchas WHERE id='$data[id]'")->fetch_assoc();
                    
                    if ($c['is_two']) {
                        if ($c['is_job']) {
                            $r = db()->query("SELECT * FROM repeats WHERE id='$c[job_id]'")->fetch_assoc();
                            if ($c['job_code'] === 1) {
                                $this->addRepeat($c, $r_input, $c['hash_one'], $c['image_id_one'], (mb_strpos($r_input, ' ') !== false));
                            } elseif ($c['job_code'] === 2) {
                                $this->addRepeat($c, $r_input, $c['hash_two'], $c['image_id_two'], (mb_strpos($r_input, ' ') !== false));
                            }
                        } else {
                            if ($data['is_only_second_part']) {
                                $this->addRepeat($c, null, $c['hash_one'], $c['image_id_one']);
                                $this->addRepeat($c, $r_input, $c['hash_two'], $c['image_id_two'], (mb_strpos($r_input, ' ') !== false));
                            } else {
                                $r_input = explode(' ', $r_input, 2);
                                if (count($r_input) === 2) {
                                    $this->addRepeat($c, $r_input[0], $c['hash_one'], $c['image_id_one'], (mb_strpos($r_input[0], ' ') !== false));
                                    $this->addRepeat($c, $r_input[1], $c['hash_two'], $c['image_id_two'], (mb_strpos($r_input[1], ' ') !== false));
                                } else {
                                    $this->addRepeat($c, $r_input[0], $c['hash_one'], $c['image_id_one'], (mb_strpos($r_input[0], ' ') !== false));
                                    $this->addRepeat($c, null, $c['hash_two'], $c['image_id_two']);
                                }
                            }
                        }
                    } else {
                        $this->addRepeat($c, $r_input, $c['hash'], $c['image_id'], (mb_strpos($r_input, ' ') !== false));
                    }
                }//is num repeats
                
                if ($data['is_two']) {
                    if (!isset($c)) {
                        $c = db()->query("SELECT * FROM captchas WHERE id='$data[id]'")->fetch_assoc();
                    }
                    
                    if ($c['is_job']) {
                        $input = trim($input);
                        $r = db()->query("SELECT * FROM repeats WHERE id='$c[job_id]'")->fetch_assoc();
                        if (!$r['is_skip']) {
                            if ($c['job_code'] === 1) {
                                $input .= ' ' . $r['input'];
                            } elseif ($c['job_code'] === 2) {
                                $input =  $r['input'] . ' ' . $input;
                            }
                        }
                    }
                    
                    if ($c['is_phrase']) {
                        if (mb_strpos($input, ' ') === false) {
                            $input .= ' ';
                        }
                    }
                }
                
                db()->query("UPDATE captchas SET input='" . db()->escape_string($input) . "', is_only_second_part='$data[is_only_second_part]' WHERE id='$data[id]'");
                $sock->sendUser($data['num_user'], 'input', $input);
                $this->sendEnted($data['id']);
                break;
            case 'skip':
                $data = json_decode($data, true);
                
                if (!$data['is_end_time']) {
                    if (db()->query("SELECT value FROM settings WHERE name='is_save_repeats'")->fetch_assoc()['value']) {
                        $c = db()->query("SELECT * FROM captchas WHERE id='$data[id]'")->fetch_assoc();

                        if ($c['is_two']) {
                            if ($c['is_job']) {
                                $r = db()->query("SELECT * FROM repeats WHERE id='$c[job_id]'")->fetch_assoc();
                                if ($c['job_code'] === 1) {
                                    $this->addRepeat($c, null, $c['hash_one'], $c['image_id_one']);
                                } elseif ($c['job_code'] === 2) {
                                    $this->addRepeat($c, null, $c['hash_two'], $c['image_id_two']);
                                }
                            }
                        } else {
                            $this->addRepeat($c, null, $c['hash'], $c['image_id']);
                        }
                    }//is num repeats
                }
                
                if ($data['is_two']) {
                    if (!isset($c)) {
                        $c = db()->query("SELECT * FROM captchas WHERE id='$data[id]'")->fetch_assoc();
                    }
                    
                    if ($c['is_job']) {
                        $r = db()->query("SELECT * FROM repeats WHERE id='$c[job_id]'")->fetch_assoc();
                        if (!$r['is_skip']) {
                            $input = $r['input'];
                            
                            if ($c['is_phrase']) {
                                if (mb_strpos($input, ' ') === false) {
                                    $input .= ' ';
                                }
                            }
                            
                            db()->query("UPDATE captchas SET input='" . db()->escape_string($input) . "', WHERE id='$data[id]'");
                            $sock->sendUser($data['num_user'], 'input', $input);
                            $this->sendEnted($data['id']);
                            break;
                        }
                    }
                }
                
                db()->query("UPDATE captchas SET is_skip=TRUE, is_time_skip='$data[is_end_time]' WHERE id='$data[id]'");
                $sock->sendUser($data['num_user'], 'skip');
                $this->sendEnted($data['id']);
                break;
            case 'set_discount':
                $data = json_decode($data);
                $sock->sendUser($data[0], 'set_discount', $data[1]);
                break;
            case 'get_discs':
                $sock->sendUsers('get_discs');
                break;
            case 'get_lang':
                $sock->sendClient('lang', file_get_contents(FILES_DIR . '/rus_lang.txt'));
                break;
            case 'add_caps':
                $caps = db()->query("SELECT width,height,mime_type FROM captchas WHERE id='$data'")->fetch_assoc();
                db()->query("INSERT INTO caps SET width='$caps[width]', height='$caps[height]', mime_type='$caps[mime_type]'");
                break;
            case 'del_repeat':
                db()->query("DELETE FROM repeats WHERE id='$data'");
                $sock->sendClient('del_repeat', $data);
                break;
            case 'get_setting':
                $data = json_decode($data, true);
                $val = db()->query("SELECT value FROM settings WHERE name='$data[name]'")->fetch_assoc()['value'];
                $sock->sendClient('res_get_setting', ['name' => $data['name'], 'value' => $val], true);
                break;
            case 'set_setting':
                $data = json_decode($data, true);
                if (is_bool($data['value'])) $data['value'] = (int)$data['value'];
                db()->query("UPDATE settings SET value='$data[value]' WHERE name='$data[name]'");
        }
    }
    
    function addRepeat($c, $input, $hash, $image_id, $is_phrase2 = false) {
        db()->query("INSERT INTO repeats SET "
                . ($input === null ? "is_skip=TRUE" : "input='" . db()->escape_string($input) . "'") . ","
                . "is_reg='$c[is_reg]',"
                . "is_num='$c[is_num]',"
                . "is_phrase2='$is_phrase2',"
                . "hash='$hash',"
                . "ts_add=UNIX_TIMESTAMP(),"
                . "image_id='$image_id'");
        
        $id = db()->insert_id;
        
        $base64 = db()->query("SELECT base64 FROM images WHERE id='$image_id'")->fetch_assoc()['base64'];
        
        $data = [
            'is_skip' => ($input === null ? true : false),
            'input' => $input,
            'base64' => $base64,
            'id' => $id,
            'is_reg' => $c['is_reg'],
            'is_num' => $c['is_num'],
            'is_phrase2' => $is_phrase2
        ];
        
        $this->socket->send($this->con, 'add_repeat', $data, true);
    }
    
    function cmdSendUsers() {
        $res = db()->query("SELECT num_user,is_display,is_pause FROM users ORDER BY num_user ASC")->fetch_all(MYSQLI_ASSOC);
        $this->socket->send($this->con, 'users', $res, true);
    }
    
    function sendEnted($id) {
        $ented = db()->query("SELECT "
                . "captchas.id,"
                . "images.base64,"
                . "captchas.input,"
                . "captchas.is_skip,"
                . "captchas.is_caps,"
                . "captchas.is_reg,"
                . "captchas.is_phrase,"
                . "captchas.is_num,"
                . "captchas.num_user"
                . " FROM captchas,images WHERE captchas.image_id=images.id AND captchas.id='$id'")->fetch_assoc();
        $this->socket->sendClient('ented', $ented, true);
    }
}
