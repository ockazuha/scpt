<?php
class MySocket extends Socket {
    public $cons = [], $search_cons = [];
    
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
        $this->log($str, self::MYSOCKET_MSG);
        
        $data = null;
        
        $msg = explode(' || ', $str, 2);
        $cmd = $msg[0];
        if (isset($msg[1])) $data = $msg[1];
        
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
                            $this->cons[$group][$username] = new ConData($con, new Client($this), $group, $username);
                        }
                    } elseif ($group === 'users') {
                        $this->cons[$group][$username] = new ConData($con, new User($this), $group, $username);
                    }
                    
                    $link_con_data = &$this->cons[$group][$username];
                    
                    $this->search_cons[] = [
                        'con' => $con,
                        'data' => $link_con_data
                    ];
                    
                    break;
            }
        }
    }
    
    function send($con, $cmd, $data = '', $json_encode = false) {
        if ($json_encode) {
            $data = jsonEncode($data);
        }
        
        $str = "$cmd || $data";
        
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
