var sock, json;
eval(userscript.getJSSource('functions'));
eval(userscript.getJSSource('socket'));

sock.init('<?=cfg('socket')['client_addr']?>', 'users', userscript.num_user, function(cmd, data) {
    switch (cmd) {
        case 'init':
            sock.send('get_user');
            break;
        case 'set_status':
            data = json.decode(data);
            if (data[0] === 'is_display') {
                $('body').css({display: (data[1] ? 'block' : 'none')});
            }
            break;
        case 'user':
            break;
    }
});