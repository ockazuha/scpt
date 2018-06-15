<?php
class MySocket extends Socket {
    public $cons = [], $search_cons = [], $requests = [], $buffer = [];
    
    const MYSOCKET_MSG = 1;
    const MYSOCKET_SEND = 2;
    const MYSOCKET_WARNING = 3;
    
    function onOpen($con, $info) {
        $this->send($con, 'hello');
    }
    
    function onClose($con) {
        $this->search($con, function($c, $key) {
            unset($this->cons[$c['data']->group][$c['data']->username]);
            unset($this->search_cons[$key]);
        });
    }
    
    function search($con, $func = null) {
        foreach ($this->search_cons as $key => $c) {
            if ($con === $c['con']) {
                if (isset($func)) $func($c, $key);
                return [$c, $key];
            }
        }
        
        return null;
    }
    
    function parseMessage($str) {
        $data = null;
        $is_buffer = false;
        $is_end_buffer = false;
        $result = [];
        
        $msg = explode(' || ', $str, 2);
        
        $cmd = $msg[0];
        if (isset($msg[1])) $data = $msg[1];
        
        $pos = mb_strpos($cmd, '}');
        $num_request = (int)mb_substr($cmd, 1, $pos-1);
        $cmd = mb_substr($cmd, $pos+1);
        
        if (mb_strpos($cmd, '[b') !== false) {
            $is_buffer = true;
            $pos = mb_strpos($cmd, ']');
            
            if (mb_strpos($cmd, '[be') !== false) {
                $is_end_buffer = true;
                $num_buffer = (int)mb_substr($cmd, 3, $pos);
                $cmd = str_replace('[be' . $num_buffer . ']', '', $cmd);
            } else {
                $num_buffer = (int)mb_substr($cmd, 2, $pos);
            }
        }
        
        $result = [
            'cmd' => $cmd,
            'data' => $data,
            'num_request' => $num_request,
            'is_buffer' => $is_buffer
        ];
        
        if ($result['is_buffer']) {
            $result['is_end_buffer'] = $is_end_buffer;
            $result['num_buffer'] = $num_buffer;
        }
        
        return $result;
    }
    
    function onMessage($con, $s_data) {
        $str = parent::onMessage($con, $s_data);
        extract($this->parseMessage($str));
        
        if ($is_buffer) {
            if ($is_end_buffer) {
                $this->buffer[$num_buffer][$num_request] = $data;
                ksort($this->buffer[$num_buffer]);
                
                $data = '';
                foreach ($this->buffer[$num_buffer] as $str_buffer) {
                    $data .= $str_buffer;
                }
                unset($this->buffer[$num_buffer]);
            } else {
                $this->buffer[$num_buffer][$num_request] = $data;
            }
        }
        
        if (isset($this->requests[(int)$con][$num_request])) {
            $this->log('Buffer', self::MYSOCKET_WARNING);
            return;
        } else {
            $this->log($str, self::MYSOCKET_MSG);
            $this->send($con, 'ok', '', false, $num_request);
            $this->requests[(int)$con][$num_request] = true;
        }
        
        if ($is_buffer and !$is_end_buffer) {
            return;
        }
        
        $con_data = $this->issetCon($con);
        
        if ($con_data !== false) {
            $con_data->obj->messageHandler($cmd, $data);
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
                    
                    $this->cons[$group][$username]->obj->data = $link_con_data;
                    
                    $this->search_cons[] = [
                        'con' => $con,
                        'data' => $link_con_data
                    ];
                    
                    $this->send($con, 'init');
                    
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
            $str = "{{$num_request}}$str";
        }
        
        $this->log($str, self::MYSOCKET_SEND);
        return parent::send($con, $str);
    }
    
    function sendUser($num_user, $cmd, $data = '', $json_encode = false) {
        if (isset($this->cons['users'][$num_user])) {
            $this->send($this->cons['users'][$num_user]->con, $cmd, $data, $json_encode);
        }
    }
    
    function sendUsers($cmd, $data = '', $json_encode = false) {
        foreach ($this->cons['users'] as $user) {
            $this->send($user->con, $cmd, $data, $json_encode);
        }
    }
    
    function sendClient($cmd, $data = '', $json_encode = false) {
        if (isset($this->cons['other']['client'])) {
            $this->send($this->cons['other']['client']->con, $cmd, $data, $json_encode);
        }
    }
    
    function issetCon($con) {
        $result = $this->search($con);
        
        if (isset($result)) {
            return $result[0]['data'];
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
