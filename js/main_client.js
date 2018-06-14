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
        case 'init':
            sock.send('get_users');
            break;
        case 'users':
            var users = json.decode(data);
            for (var key in users) {
                var user = users[key];
                if (!$('#users table tr').is('#id' + user['num_user'])) {
                    $('#users table').append('\n\
                    <tr id="id' + user['num_user'] + '">\n\
                    <td>' + user['num_user'] + '</td>\n\
                    <td><button onclick="setStatus(' + user['num_user'] + ', \'is_display\')" class="is_display"></button></td>\n\
                    <td><button onclick="setStatus(' + user['num_user'] + ', \'is_pause\')" class="is_pause"></button></td>\n\
                    </tr>');
                }
                
                $('#users table tr#id' + user['num_user']).find('button.is_display').html(user['is_display']);
                $('#users table tr#id' + user['num_user']).find('button.is_pause').html(user['is_pause']);
            }
            break;
    }
});

function setStatus(num_user, type) {
    sock.send('set_status', [num_user, type], true);
}

function setStatusAll(type, value) {
    sock.send('set_status_all', [type, value], true);
}
