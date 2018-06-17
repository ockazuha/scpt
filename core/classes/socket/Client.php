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
                $data = json_decode($data);
                
                if ($data[3]) {//is_caps
                    db()->query("UPDATE caps SET count=count+1 WHERE id='$data[4]'");
                    $data[0] = mb_strtoupper($data[0]);
                }
                
                $data[0] = db()->escape_string($data[0]);
                db()->query("UPDATE captchas SET input='$data[0]' WHERE id='$data[1]'");
                $sock->sendUser($data[2], 'input', $data[0]);
                
                $this->sendEnted($data[1]);
                break;
            case 'skip':
                $data = json_decode($data);
                db()->query("UPDATE captchas SET is_skip=TRUE WHERE id='$data[0]'");
                $sock->sendUser($data[1], 'skip');
                $this->sendEnted($data[0]);
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
        }
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
