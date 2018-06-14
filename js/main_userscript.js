var sock, json;
eval(userscript.getJSSource('functions'));
eval(userscript.getJSSource('socket'));

sock.init('<?=cfg('socket')['client_addr']?>', 'users', userscript.num_user, function(cmd, data) {
    switch (cmd) {
        
    }
});