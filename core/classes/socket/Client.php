<?php
class Client extends Group {
    function messageHandler($con, $cmd, $data, $num_request) {
        $sock = $this->socket;
        
        switch ($cmd) {
            case 'exit':
                exit();
                break;
        }
    }
}
