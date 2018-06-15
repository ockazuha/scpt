const SOCK_WARNING = 3;
const SOCK_SEND = 2;
const SOCK_MSG = 1;
const SOCK_SEND_AGAIN = 4;
const SOCK_BUFFER_SIZE = <?=cfg('socket')['buffer_size']?>;

sock = {
    //h, group, username, messageHandler
    requests: [],
    num_requests: 0,
    timeout_check: <?=cfg('socket')['timeout_check']?>,
    
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
        
        if (cmd.indexOf('{') === 0) {
            pos = cmd.indexOf('}');
            var num_request = cmd.substring(1, pos);
            sock.requests[+num_request] = true;
            cmd.replace('{' + num_request + '}', '');
        }
        
        sock.messageHandler(cmd, data);
    },
    
    send: function(cmd, data = '', is_json_encode = false) {
        var buffer = [];
        var buffer_num = null;
        
        if (is_json_encode) {
            data = json.encode(data);
        }
        
        if (data.length > SOCK_BUFFER_SIZE) {
            for (var i = 0; i < data.length; i+=SOCK_BUFFER_SIZE) {
                buffer.push(data.substr(i, SOCK_BUFFER_SIZE));
            }
        }
        
        var send = function(data) {
            var num_requests = sock.num_requests;
            sock.num_requests++;
            if (buffer_num === null) {
                buffer_num = num_requests;
            }
            
            var str = '{' + num_requests + '}' + (buffer.length ? (+key === (buffer.length-1) ? '[be' + buffer_num + ']' : '[b' + buffer_num + ']') : '') + cmd + ' || ' + data;
            sock.log(str, SOCK_SEND);

            sock.requests[num_requests] = false;

            setTimeout(function checkRequest() {
                if (!sock.requests[num_requests]) {
                    sock.log(str, SOCK_SEND_AGAIN);
                    sock.h.send(str);
                    setTimeout(checkRequest, sock.timeout_check);
                }
            }, sock.timeout_check);

            sock.h.send(str);
        };
        
        if (buffer.length) {
            for (var key in buffer) {
                send(buffer[key]);
            }
        } else {
            send(data);
        }
    },
    
    log: function(str, type = null) {
        if ('<?=cfg('socket')['is_log_client']?>' === '1' || type === SOCK_WARNING) {
            var prefix = '';

            if (type === SOCK_MSG) {
                prefix = '<< ';
            } else if (type === SOCK_WARNING) {
                prefix = '!!! ';
            } else if (type === SOCK_SEND) {
                prefix = '> ';
            } else if (type === SOCK_SEND_AGAIN) {
                prefix = '!> ';
            }
            
            console.log('SOCK: ' + prefix + str);
        }
    }
};