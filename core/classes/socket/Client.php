<?php
class Client extends Group {
    function messageHandler($cmd, $data) {
        switch ($cmd) {
            case 'exit':
                exit();
                break;
        }
    }
}
