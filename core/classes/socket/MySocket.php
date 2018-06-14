<?php
class MySocket extends Socket {
    public $cons = [], $search_cons = [], $requests = [], $buffer = [];
    
    const MYSOCKET_WARNING = 3;
    const MYSOCKET_SEND = 2;
    const MYSOCKET_MSG = 1;
    
    function onOpen($con, $info) {
        $this->send($con, 'hello');
    }
    
    function onClose($con) {
        foreach ($this->search_cons as $key => $c) {
            if ($con === $c['con']) {
                unset($this->cons[$c['data']->group][$c['data']->username]);
                unset($this->search_cons[$key]);
                break;
            }
        }
    }
    
    function onMessage($con, $s_data) {
        $str = parent::onMessage($con, $s_data);
        
        $data = null;
        
        $msg = explode(' || ', $str, 2);
        $cmd = $msg[0];
        if (isset($msg[1])) $data = $msg[1];
        
        $pos = mb_strpos($cmd, '}');
        
        $num_request = (int)mb_substr($cmd, 1, $pos-1);
        $cmd = mb_substr($cmd, $pos+1);
        
        if (mb_strpos($cmd, '[b') !== false) {
            $pos = mb_strpos($cmd, ']');
            
            if (mb_strpos($cmd, '[be') !== false) {
                $num_buffer = (int)mb_substr($cmd, 3, $pos);
                $cmd = str_replace('[be' . $num_buffer . ']', '', $cmd);
                $this->buffer[$num_buffer][$num_request] = $data;
                ksort($this->buffer[$num_buffer]);
                
                $data = '';
                foreach ($this->buffer[$num_buffer] as $str_buffer) {
                    $data .= $str_buffer;
                }
                unset($this->buffer[$num_buffer]);
            } else {
                $num_buffer = (int)mb_substr($cmd, 2, $pos);
                $this->buffer[$num_buffer][$num_request] = $data;
                $is_buffer = true;
            }
        }
        
        if (isset($this->requests[(int)$con][$num_request])) {
            $this->log('povtor');
            return;
        } else {
            $this->log($str, self::MYSOCKET_MSG);
            $this->send($con, 'ok', '', false, $num_request);
            $this->requests[(int)$con][$num_request] = true;
        }
        
        if (isset($is_buffer)) {
            return;
        }
        
        $con_data = $this->issetCon($con);
        
        if ($con_data !== false) {
            $con_data->obj->messageHandler($con, $cmd, $data, $num_request);
        } else {
            switch ($cmd) {
                case 'hello':
                    $data = json_decode($data);
                    $group = $data[0];
                    $username = $data[1];
                    
                    if ($group === 'other') {
                        if ($username === 'client') {
                            $this->cons[$group][$username] = new ConData($con, new Client($this, $con), $group, $username);
                        }
                    } elseif ($group === 'users') {
                        $this->cons[$group][$username] = new ConData($con, new User($this, $con), $group, $username);
                    }
                    
                    $link_con_data = &$this->cons[$group][$username];
                    
                    $this->search_cons[] = [
                        'con' => $con,
                        'data' => $link_con_data
                    ];
                    
                    $this->send($con, 'init', '', false, $num_request);
                    
                    break;
            }
        }
    }
    
    function send($con, $cmd, $data = '', $json_encode = false, $num_request = null) {
        if ($json_encode) {
            $data = jsonEncode($data);
        }
        
        $str = "$cmd || $data";
        
        if (isset($num_request)) {
            $str = '{' . $num_request . '}' . $str;
        }
        
        $this->log($str, self::MYSOCKET_SEND);
        $result = parent::send($con, $str);
    }
    
    function issetCon($con) {
        foreach ($this->search_cons as $c) {
            if ($con === $c['con']) {
                return $c['data'];
            }
        }
        
        return false;
    }
    
    function log($str, $type = null) {
        if (cfg('socket')['is_log_server']) {
            $prefix = '';
            
            if ($type === self::MYSOCKET_SEND) {
                $prefix = '> ';
            } elseif ($type === self::MYSOCKET_MSG) {
                $prefix = '<< ';
            } elseif ($type === self::MYSOCKET_WARNING) {
                $prefix = '!!! ';
            }
            
            echo "{$prefix}$str\n";
        }
    }
}
