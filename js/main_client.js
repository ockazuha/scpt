/* global checked */

var client = {
    domain: "<?=cfg('domain')?>",
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
    max_time: +"<?=cfg('client')['max_time']?>",
    is_log: func.bool("<?=cfg('client')['is_log']?>"),
    num_users: +"<?=cfg('client')['num_users']?>"
};

var dat = {
    capts: [],
    users: [],
    is_init_sum: false,
    job_time: 0,
    pos_top: false // реверс
    // usd, start_sum, ts_job_time
};

client.xhr.open('GET', 'https://www.cbr-xml-daily.ru/daily_json.js', false);
client.xhr.send();
var usd = json.decode(client.xhr.responseText);
dat.usd = usd['Valute']['USD']['Value'];

sock.init("<?=cfg('socket')['client_addr']?>", 'other', 'client', function(cmd, data) {
    switch (cmd) {
        case 'init':
            sock.send('get_users');
            break;
        case 'ented':
            data = json.decode(data);
            $('#ented').prepend('\n\
                <div class="ented" id="ented' + data.id + '">\n\
                    <table class="types">\n\
                        <tr>\n\
                        <td' + (func.bool(data.is_reg) ? ' class="active"' : '') + '>Рег</td>\n\
                        <td' + (func.bool(data.is_phrase) ? ' class="active"' : '') + '>Два</td>\n\
                        <td' + (func.bool(data.is_num) ? ' class="active"' : '') + '>Цифр</td>\n\
                        <td' + (func.bool(data.is_caps) ? ' class="active"' : '') + '>' + (func.bool(data.is_caps) ? 'CAPS' : '<button onclick="sock.send(\'add_caps\', ' + data.id + ')"></button>') + '</td>\n\
                        </tr>\n\
                    </table>\n\
                    <div class="image" style="background-image: url(\'' + data.base64 + '\')"></div>\n\
                    <div class="input">[' + data.id + '] ' + data.num_user + ': ' + (func.bool(data.is_skip) ? '-SKIP-' : data.input) + '</div>\n\
                </td>');
            break;
        case 'users':
            var users = json.decode(data);
            for (var key in users) {
                var user = users[key];
                if (!$('#users tr').is('#user' + user['num_user'])) {
                    $('#users table').append('\n\
                        <tr id="user' + user['num_user'] + '">\n\
                            <td>' + user['num_user'] + '</td>\n\
                            <td class="btn"><button onclick="setStatus(' + user['num_user'] + ', \'is_pause\')" class="is_pause"></button></td>\n\
                            <td class="btn"><button onclick="setStatus(' + user['num_user'] + ', \'is_display\')" class="is_display"></button></td>\n\
                            <td class="discs">\n\
                                <button class="disc disc0" onclick="setDiscount(' + user['num_user'] + ', 0)">0</button><!--\n\
                                --><button class="disc disc10" onclick="setDiscount(' + user['num_user'] + ', 10)">10</button><!--\n\
                                --><button class="disc disc20" onclick="setDiscount(' + user['num_user'] + ', 20)">20</button><!--\n\
                                --><button class="disc disc30" onclick="setDiscount(' + user['num_user'] + ', 30)">30</button><!--\n\
                                --><button class="disc disc40" onclick="setDiscount(' + user['num_user'] + ', 40)">40</button><!--\n\
                                --><button class="disc disc50" onclick="setDiscount(' + user['num_user'] + ', 50)">50</button>\n\
                            </td>\n\
                            \n\
                            <td class="sum text_right"></td>\n\
                            <td class="balance text_right"></td>\n\
                            <td class="accum text_right"></td>\n\
                            <td class="accum_count text_right"></td>\n\
                            <td class="priority text_right"></td>\n\
                            <td class="level_perc text_right"></td>\n\
                            <td class="solved text_right"></td>\n\
                            <td class="skips_left text_right"></td>\n\
                            <td class="title"></title>\n\
                        </tr>');
                }
                
                $('#user' + user['num_user']).find('.is_display').html(+user['is_display'] ? 'Скрыть' : 'Показать');
                $('#user' + user['num_user']).find('.is_pause').html(+user['is_pause'] ? 'Старт' : 'Пауза');
            }
            sock.send('get_lang');
            sock.send('get_discs');
            sock.send('get_setting', {name:'is_save_repeats'}, true);
            break;
        case 'res_get_setting':
            data = json.decode(data);
            
            if (data.name === 'is_save_repeats') {
                $("#is_save_repeats").prop('checked',func.bool(data.value));
                // лучше выполнять 1 раз, т.е. создать переменную что тут инициализированно
                $("#is_save_repeats").change(function() {
                    sock.send('set_setting', {name: 'is_save_repeats', value: this.checked}, true);
                });
            }
            break;
        case 'lang':
            if (data === '1') {
                $('#input').css('background-color', '#fff8c5');
            } else {
                $('#input').css('background-color', '#fff');
            }
            break;
        case 'capt':
            var data = json.decode(data);
            
            dat.pos_top = !dat.pos_top;
            var pos_class = 'pos_' + (dat.pos_top ? 'top' : 'bottom');
            
            if (!getNumCapts()) {
                $('#input').val('');
            }
            
            data['is_reg'] = +data['is_reg'];
            data['is_num'] = +data['is_num'];
            data['is_phrase'] = +data['is_phrase'];
            
            var types = '\n\
                <table class="types">\n\
                    <tr>\n\
                        <td class="is_reg' + (+data['is_reg'] ? ' active' : '') + '">Регистр</td>\n\
                        <td class="is_phrase' + (+data['is_phrase'] ? ' active' : '') + '">Два слова</td>\n\
                        <td class="is_num' + (+data['is_num'] ? ' active' : '') + '">Цифры</td>\n\
                    </tr>\n\
                </table>';
                    
            var info = '\n\
                <table class="info">\n\
                    <tr>\n\
                        <td class="num_user">' + data['num_user'] + '</td>\n\
                        <td class="id">' + data['id'] + '</td>\n\
                        <td class="bid">' + func.fixed(parseFloat(data['bid'])*1000000, 0) + '</td>\n\
                        <td class="timer">' + mathTimer(data['ts_add']) + '</td>\n\
                    </tr>\n\
                </table>';
            
            $('#capts').append('\n\
                <div class="capt ' + pos_class + '" id="capt' + data['id'] + '">\n\
                    ' +  (dat.pos_top ? info + types : '') + '\n\
                    <div class="image" style="background-image: url(\'' + data['base64'] + '\')"></div>\n\
                    ' +  (!dat.pos_top ? types + info : '') + '\n\
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
            
            if (dat.users[data.num_user] === undefined) dat.users[data.num_user] = {};
            dat.users[data.num_user] = Object.assign(dat.users[data.num_user], data);
            
            if (data.is_full_stat) {
                var sum = parseFloat(data.balance) + (parseFloat(data.accum)*+('1.' + data.level_perc));
                
                if (!isNaN(sum)) {
                    find('accum', func.fixed(data.accum, 5));
                    find('accum_count', data.accum_count);
                    find('balance', func.fixed(data.balance, 2));
                    find('priority', func.fixed(data.priority, 2));
                    find('solved', data.solved);
                    find('level_perc', data.level_perc + '%');
                    find('skips_left', data.skips_left);
                    find('sum', func.fixed(sum*dat.usd, 2));

                    dat.users[data.num_user].sum = sum;
                }
            }
            
            if (!dat.is_init_sum) {
                var is_init_sum = true;
                for (var i = 1; i <= sett.num_users; i++) {
                    if (dat.users[i] === undefined) {
                        is_init_sum = false;
                        break;
                    }
                }

                if (is_init_sum) {
                    dat.start_sum = 0;
                    var is_correct_sum = true;
                    
                    for (var key in dat.users) {
                        if (dat.users[key].sum === undefined) {
                            is_correct_sum = false;
                            break;
                        }
                    }
                    
                    if (is_correct_sum) {
                        for (var key in dat.users) {
                            dat.start_sum += dat.users[key].sum;
                        }

                        dat.start_sum *= dat.usd;
                        dat.is_init_sum = true;
                    }
                }
            }
            
            break;
    }
});

setInterval(function() {
    var sum = 0;
    for (var key in dat.users) {
        if (dat.users[key].sum !== undefined) {
            sum += dat.users[key].sum;
        }
    }
    sum*=dat.usd;
    $('#users').find('.profit').html(func.fixed(sum, 2));
    if (dat.is_init_sum) {
        var profit = sum - dat.start_sum;
        $('#users').find('.profit_session').html(func.fixed(profit, 2));
        
        // var hour_speed = session_data['profit']*(3600/$data.job_time);
        if (dat.job_time !== 0) {
            $('#users').find('.speed_hour').html(func.fixed(profit*(3600/dat.job_time),2));
        }
    }
}, 750);

setInterval(function() {
    for (var key in dat.capts) {
        var capt = dat.capts[key];
        var time = mathTimer(capt.ts_add);
        
        if (time <= 0) skip(capt.id, true);
        else $('#capt' + capt.id).find('.timer').html(time);
    }
}, 1000);

setInterval(function() {
    if (getNumCapts()) {
        if (dat.ts_job_time === undefined) {
            dat.ts_job_time = func.microtime();
        } else {
            dat.job_time += func.microtime() - dat.ts_job_time;
            dat.ts_job_time = func.microtime();
        }
    } else {
        if (dat.ts_job_time !== undefined) {
            dat.job_time += func.microtime() - dat.ts_job_time;
            dat.ts_job_time = undefined;
        }
    }
}, 50);

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
    
    var is_only_first_part = false, is_only_second_part = false;
    
    if (input.indexOf('-') === 0) {
        is_only_first_part = true;
        input = input.substr(1);
    } else if (input.indexOf('-') === (input.length-1)) {
        is_only_second_part = true;
        input = input.substr(0, input.length-1);
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
    
    //log('>>> ENTER: ' + id + '|"' + input + '"');
    
    var send_data = {
        input: input,
        id: id,
        num_user: num_user,
        is_caps: dat.capts[id].is_caps,
        is_only_first_part: is_only_first_part,
        is_only_second_part: is_only_second_part
    };
    
    if (dat.capts[id].is_caps) send_data.id_caps = dat.capts[id].id_caps;
    
    delete(dat.capts[id]);
    $('#capt' + id).remove();
    
    sock.send('input', send_data, true);
}

function skip(id, is_end_time = false) {
    if (typeof(id) === 'string') id = +id;
    if (isNaN(id)) return;
    
    if (getFirstId() === id) $('#input').val('');
    
    var num_user = dat.capts[id].num_user;
    
    delete(dat.capts[id]);
    $('#capt' + id).remove();
    
    log('>>> SKIP: ' + id);
    
    var data_send = {
        id: id,
        num_user: num_user,
        is_end_time: is_end_time
    };
    
    sock.send('skip', data_send, true);
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
    switch (e.which) {
        case 13:
            $('#input').val($('#input').val() + ' ');
            break;
        case 27:
            skip(getFirstId());
            break;
        case 32:
            send();
            break;
        case 119:
            setStatusAll('is_pause', false)
            break;
        case 19:
            setStatusAll('is_pause', true);
            break;
    }
});

setInterval(function() {
    sock.send('get_lang');
}, 300);

$(document).keyup(function(e) {
    switch (e.which) {
        case 9:
            sock.send('get_lang');
            break;
    }
});
