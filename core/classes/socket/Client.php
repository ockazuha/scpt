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
            case 'test':
                db()->escape_string('123');
                break;
            case 'input':
                $data = json_decode($data);
                $data[0] = db()->escape_string($data[0]);
                db()->query("UPDATE captchas SET input='$data[0]' WHERE id='$data[1]'");
                $sock->sendUser($data[2], 'input', $data[0]);
                break;
            case 'skip':
                $data = json_decode($data);
                db()->query("UPDATE captchas SET is_skip=TRUE WHERE id='$data[0]'");
                $sock->sendUser($data[1], 'skip');
                break;
            case 'set_discount':
                $data = json_decode($data);
                $sock->sendUser($data[0], 'set_discount', $data[1]);
                break;
        }
    }
    
    function cmdSendUsers() {
        $res = db()->query("SELECT num_user,is_display,is_pause FROM users ORDER BY num_user ASC")->fetch_all(MYSQLI_ASSOC);
        $this->socket->send($this->con, 'users', $res, true);
    }
}
