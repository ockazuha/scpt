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
        }
    }
    
    function cmdSendUsers() {
        $res = db()->query("SELECT num_user,is_display,is_pause FROM users ORDER BY num_user ASC")->fetch_all(MYSQLI_ASSOC);
        $this->socket->send($this->con, 'users', $res, true);
    }
}
