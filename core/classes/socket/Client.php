<?php
class Client extends Group {
    function messageHandler($cmd, $data) {
        $sock = $this->socket;
        
        switch ($cmd) {
            case 'exit':
                exit();
                break;
            case 'test':
                print_r($sock->cons);
                print_r($sock->search_cons);
                break;
        }
    }
}
