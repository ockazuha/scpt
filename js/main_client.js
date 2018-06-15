var client = {
    domain: '<?=cfg('domain')?>',
    xhr: new XMLHttpRequest(),

    getJSSource: function(name) {
        this.xhr.open('GET', 'http://' + this.domain + '/get_js_source.php?name=' + name + '&<?=VER?>', false);
        this.xhr.send();
        return this.xhr.responseText;
    }
};

var sock, json, func;
eval(client.getJSSource('functions'));
eval(client.getJSSource('socket'));

var sett = {
    max_time: <?=cfg('client')['max_time']?>,
    is_log: func.bool('<?=cfg('client')['is_log']?>'),
};

var dat = {
    capts: []
};

sock.init('<?=cfg('socket')['client_addr']?>', 'other', 'client', function(cmd, data) {
    switch (cmd) {
        case 'init':
            sock.send('get_users');
            break;
        case 'users':
            var users = json.decode(data);
            for (var key in users) {
                var user = users[key];
                if (!$('#users table tr').is('#user' + user['num_user'])) {
                    $('#users table').append('\n\
                    <tr id="user' + user['num_user'] + '">\n\
                    <td>' + user['num_user'] + '</td>\n\
                    <td class="btn"><button onclick="setStatus(' + user['num_user'] + ', \'is_display\')" class="is_display"></button></td>\n\
                    <td class="btn"><button onclick="setStatus(' + user['num_user'] + ', \'is_pause\')" class="is_pause"></button></td>\n\
                    </tr>');
                }
                
                $('#users table tr#user' + user['num_user']).find('button.is_display').html(+user['is_display'] ? 'Скрыть' : 'Показать');
                $('#users table tr#user' + user['num_user']).find('button.is_pause').html(+user['is_pause'] ? 'Старт' : 'Пауза');
            }
            break;
        case 'capt':
            var data = json.decode(data);
            
            if (!getNumCapts()) {
                $('#input').val('');
            }
            
            data['is_reg'] = +data['is_reg'];
            data['is_num'] = +data['is_num'];
            data['is_phrase'] = +data['is_phrase'];
            
            $('#capts').append('\n\
            <div class="capt" id="capt' + data['id'] + '">\n\
                <div class="types">\n\
                    <div class="is_reg' + (+data['is_reg'] ? ' active' : '') + '">Регистр</div><!--\n\
                    --><div class="is_phrase' + (+data['is_phrase'] ? ' active' : '') + '">Два слова</div><!--\n\
                    --><div class="is_num' + (+data['is_num'] ? ' active' : '') + '">Цифры</div>\n\
                </div>\n\
                <div class="image" style="background-image: url(\'' + data['base64'] + '\')"></div>\n\
                <div class="timer">' + mathTimer(data['ts_add']) + '</div>\n\
                <div class="num_user">' + data['num_user'] + '</div>\n\
                <div class="id">' + data['id'] + '</div>\n\
            </div>');
            
            dat.capts[data['id']] = data;
    }
});

setInterval(function() {
    for (var key in dat.capts) {
        var capt = dat.capts[key];
        var time = mathTimer(capt.ts_add);
        
        if (time <= 0) skip(capt.id);
        else $('#capt' + capt.id).find('.timer').html(time);
    }
}, 1000);

function setStatus(num_user, type) {
    sock.send('set_status', [num_user, type], true);
}

function setStatusAll(type, value) {
    sock.send('set_status_all', [type, value], true);
}

function mathTimer(ts_add) {
    return Math.floor(sett.max_time - (func.microtime() - ts_add));
}

function getNumCapts() {
    return Object.keys(dat.capts).length;
}

function getFirstId() {
    return +$('.capt:first-child').find('.id').html();
}

function send() {
    var id = getFirstId();
    if (isNaN(id)) return;
    
    var input = $('#input').val().trim();
    $('#input').val('');
    
    if (input === '') {
        skip(id);
        return;
    }
    
    if (dat.capts[id]['is_num']) {
        if (input.match(/^[0-9 ]{1,32}$/) === null) {
            skip(id);
            return;
        }
    }
    
    if (dat.capts[id]['is_phrase']) {
        if (input.search(' ') === -1) {
            skip(id);
            return;
        }
    }
    
    var num_user = dat.capts[id].num_user;
    
    delete(dat.capts[id]);
    $('#capt' + id).remove();
    
    log('>>> ENTER: ' + id + '|"' + input + '"');
    sock.send('input', [input, id, num_user], true);
}

function skip(id) {
    if (typeof(id) === 'string') id = +id;
    if (isNaN(id)) return;
    
    if (getFirstId() === id) $('#input').val('');
    
    var num_user = dat.capts[id].num_user;
    
    delete(dat.capts[id]);
    $('#capt' + id).remove();
    
    log('>>> SKIP: ' + id);
    sock.send('skip', [id, num_user], true);
}

$('#input').focusout(function() {
    $('#input').focus();
});

function log(str) {
    if (sett.is_log) {
        console.log('LOG: ' + str);
    }
}

$(document).keydown(function(e) {
    var key = e.which;
    
    if (key == 13) {
        $('#input').val($('#input').val() + ' ');
    } else if (key == 27) {
        skip(getFirstId());
    } else if (key == 32) {
        send();
    }
});