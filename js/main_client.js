var client = {
    domain: '<?=cfg('domain')?>',
    xhr: new XMLHttpRequest(),

    getJSSource: function(name) {
        this.xhr.open('GET', 'http://' + this.domain + '/get_js_source.php?name=' + name + '&<?=VER?>', false);
        this.xhr.send();
        return this.xhr.responseText;
    }
};

var sock, json;
eval(client.getJSSource('functions'));
eval(client.getJSSource('socket'));

sock.init('<?=cfg('socket')['client_addr']?>', 'other', 'client', function(cmd, data) {
    switch (cmd) {
        
    }
});
