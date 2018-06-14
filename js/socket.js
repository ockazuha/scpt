const SOCK_WARNING = 3;
const SOCK_SEND = 2;
const SOCK_MSG = 1;

sock = {
    //h, group, username, messageHandler
    
    init: function(addr, group, username, messageHandler) {
        this.group = group;
        this.username = username;
        this.messageHandler = messageHandler;
        
        this.h = new WebSocket(addr);
        this.h.onopen = this.onOpen; 
        this.h.onmessage = this.onMessage;
    },
    
    onOpen: function() {
        sock.send('hello', json.encode([sock.group, sock.username]));
    },
    
    onMessage: function(e) {
        var cmd = e.data;
        var data = '';
        var str = ' || ';
        
        sock.log(cmd, SOCK_MSG);
        
        var pos = cmd.indexOf(str);
        
        if (cmd !== -1) {
            var data = cmd.substring(pos + str.length);
            cmd = cmd.substring(0, pos);
        }
        
        sock.messageHandler(cmd, data);
    },
    
    send: function(cmd, data = '') {
        var str = cmd + ' || ' + data;
        this.log(str, SOCK_SEND);
        return this.h.send(str);
    },
    
    log: function(str, type = null) {
        if ('<?=cfg('socket')['is_log_client']?>' === '1') {
            var prefix = '';

            if (type === SOCK_MSG) {
                prefix = '<< ';
            } else if (type === SOCK_WARNING) {
                prefix = '!!! ';
            } else if (type === SOCK_SEND) {
                prefix = '> ';
            }
            
            console.log('SOCK: ' + prefix + str);
        }
    }
};