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
    capts: [],
    users: []
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
<td class="discs">\n\
<button class="disc disc0" onclick="setDiscount(' + user['num_user'] + ', 0)">0</button><!--\n\
--><button class="disc disc10" onclick="setDiscount(' + user['num_user'] + ', 10)">10</button><!--\n\
--><button class="disc disc20" onclick="setDiscount(' + user['num_user'] + ', 20)">20</button><!--\n\
--><button class="disc disc30" onclick="setDiscount(' + user['num_user'] + ', 30)">30</button><!--\n\
--><button class="disc disc40" onclick="setDiscount(' + user['num_user'] + ', 40)">40</button><!--\n\
--><button class="disc disc50" onclick="setDiscount(' + user['num_user'] + ', 50)">50</button>\n\
</td>\n\
\n\
<td class="sum"></td>\n\
<td class="balance"></td>\n\
<td class="accum"></td>\n\
<td class="accum_count"></td>\n\
<td class="priority"></td>\n\
<td class="level_perc"></td>\n\
<td class="solved"></td>\n\
<td class="skips_left"></td>\n\
<td class="title"></title>\n\
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
            break;
        case 'curr_discount':
            var data = json.decode(data);
            $('#user' + data[0]).find('.disc').attr('disabled', false);
            $('#user' + data[0]).find('.disc' + data[1]).attr('disabled', true);
            break;
        case 'stat':
            data = json.decode(data);
            
            var find = function(_class, html) {
                $('#user'+data['num_user']).find('.'+_class).html(html);
            };
            
            find('title', data.title);
            
            if (data.is_full_stat) {
                find('accum', func.fixed(data.accum, 5));
                find('accum_count', data.accum_count);
                find('balance', func.fixed(data.balance, 2));
                find('priority', func.fixed(data.priority, 2));
                find('solved', data.solved);
                find('level_perc', '+' + data.level_perc + '%');
                find('skips_left', data.skips_left);
                
                find('sum', func.fixed(parseFloat(data.balance) + (parseFloat(data.accum)*+('1.' + data.level_perc)), 3));
            }
            
            dat.users[data.num_user] = data;
            
            break;
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

function setDiscount(num_user, val) {
    sock.send('set_discount', [num_user, val], true);
}

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