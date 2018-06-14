<?php
class Client extends Group {
    function messageHandler($con, $cmd, $data, $num_request) {
        $sock = $this->socket;
        
        switch ($cmd) {
            case 'exit':
                exit();
                break;
            case 'test':
                $str = '';
                for ($i = 0; $i < 499999; $i++) {
                    $str .= '1';
                }
                $str .= '2';
                $sock->send($con, 'kek', $str);
                break;
            case 'test2':
                print_r($sock->requests);
                break;
        }
    }
}
