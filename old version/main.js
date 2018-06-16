// ФУНКЦИИ
function updateAccounts() {
    $.get('app/get_accounts_data', function(json) {
        var accounts_data = $.parseJSON(json);
        $data.accounts = accounts_data;
        
        for (var key in accounts_data) {
            var account = accounts_data[key];
            
            if (!$('tr').is('#account-' + account['id'])) {
                $('#accounts table').append('\n\
                    <tr class="account" id="account-' + account['id'] + '">\n\
                        <td>' + account['num_chrome_user'] + '</td>\n\
                        <td><button class="pause" onclick="pauseAccount(' + account['id'] + ')"></button>\n\
                        <td><button class="visible" onclick="visibleAccount(' + account['id'] + ')"></button>\n\
                        <td><span class="sec-last-captcha"></span> с.н.</td>\n\
                        <td><span class="sec-last-update"></span> с.н.</td>\n\
                        <td><span class="title"></span></td>\n\
                    </tr>');
            }
            
            var sec_last_update = system.fixed(system.microtime() - account['ts_last_update_us'], 0);
            var sec_last_captcha = system.fixed(system.microtime() - account['ts_last_captcha_us'], 0);
            
            var tr = $('#account-' + account['id']);
            tr.find('.title').html(account['title']);
            tr.find('.sec-last-captcha').html(sec_last_captcha);
            tr.find('.sec-last-update').html(sec_last_update);
            
            var updateButtons = function(class_name, green_text, red_text) {
                var btn = tr.find('.' + class_name);
                if (+account[class_name]) {
                    if (!btn.hasClass('red')) {
                        btn.attr('class', class_name + ' red');
                        btn.html(red_text);
                    }
                } else {
                    if (!btn.hasClass('green')) {
                        btn.attr('class',  class_name + ' green');
                        btn.html(green_text);
                    }
                }
            };
            
            updateButtons('pause', 'Стоп', 'Старт');
            updateButtons('visible', 'Показать', 'Скрыть');
        }
    });
}

function pauseAccount(account_id) {
    $.get('app/invert_status?name=pause&account_id=' + account_id);
    updateAccounts();
}

function visibleAccount(account_id) {
    $.get('app/invert_status?name=visible&account_id=' + account_id);
    updateAccounts();
}

function pauseAll() {
    $.get('app/management_all?name=pause&value=' + 1);
    updateAccounts();
}

function startAll() {
    $.get('app/management_all?name=pause&value=' + 0);
    updateAccounts();
}

function getNumCaptchas() {
    return Object.keys($data.captchas).length;
}

function getFirstId() {
    return +$('.captcha:first-child').find('.id').html();
}

function mathTimer(ts) {
    return Math.floor(32 - (system.microtime() - +ts));
}

function send(id) {
    var input = $('#input').val();
    
    if (+$data.captchas[id].caps) {
        input = input.toUpperCase();
        console.log('CAPS: ' + input);
    }

    if (+$data.captchas[id]['is_numeric']) {
        if (input.match(/^[0-9 ]{1,40}$/) === null) {
            skip(id);
            return;
        }
    }

    if (+$data.captchas[id]['is_phrase']) {
        if (input.search(' ') === -1) {
            skip(id);
            return;
        }
    }

    $('#input').val('');
    delete($data.captchas[id]);
    $('#captcha-'+id).remove(); // (старый непонятный коммент) вдруг в капче удалятся первые символы...

    console.log('>>> ENTER CAPTCHA: ' + id + '|"' + input + '"');
    $.get('app/set_input?id='+id+'&input='+encodeURIComponent(input));
}

function skip(id, is_time_skip = false) {
    if (getFirstId() === id) $('#input').val('');

    delete($data.captchas[id]);
    $('#captcha-' + id).remove();
    
    console.log('>>> SKIP CAPTCHA: ' + id + ' is_time_skip=' + (+is_time_skip));
    $.get('app/set_input?id='+id+'&is_skip=1&is_time_skip=' + (+is_time_skip));
}

function updateLang() {
    $.get('app/get_lang', function(lang) {
        if (settings.lang !== +lang) {
            settings.lang = +lang;
            $('#lang').html((+lang ? 'РУС' : 'EN'));
            $('#lang').attr('style', 'background-color: ' + (+lang ? '#ffc107' : '#fff'));
        }
    });
}

var system = {
    //возвращает строку. округляет вниз. сохраняет нули в конце. приводит к флоат
    fixed: function(num, len) {
        num = parseFloat(num);
        var pow = Math.pow(10, len);
        return (parseInt(num*pow)/pow).toFixed(len);
    },
    
    microtime: function() {
        var date = new Date();
        return date.getTime()/1000;
    }
};


// НАСТРОЙКИ И КОД
var settings = {
    position_captcha_first: false,//перед изменением идет реверс...
    sound: true,
    lang: -1
};

var $data = {
    //accounts
    ts_job_time: undefined,
    captchas: {},
    job_time: 0
};

var tm = {
    get_captchas: 250
};

$.get("app/get_setting?name=tm_get_captchas", function(html) {
    tm.get_captchas = +html;
});

function getTm() {
    
    $.get("app/get_setting?name=tm_get_input", function(html) {
        console.log('get_input='+html);
        $.get("app/get_setting?name=tm_cap_hand", function(html) {
            console.log('cap_hand='+html);
            $.get("app/get_setting?name=tm_check_skip", function(html) {
                console.log('check_skip='+html);
                console.log('get_captchas='+tm.get_captchas);
            });
        });
    });
}

function setTm(name, val) {
    $.get("app/set_setting?name=tm_" + name + "&value=" + val, function(html) {
        console.log('OK');
    });
}


$.get("app/get_setting?name=save_repeats", function(html) {
    $("#is_save_repeats").prop("checked", Boolean(+html));
});

$.get('app/clear_profit');

updateAccounts();
$('#input').focusout(function() {$('#input').focus();});

setInterval(updateLang, 500);

// stats update
setInterval(function() {
    $.get('app/get_session_data', function(json) {
        var session_data = $.parseJSON(json);
        session_data['profit'] *= 60;
        session_data['repeats_profit'] *= 60;
        
        var hour_speed = session_data['profit']*(3600/$data.job_time);
        if (isNaN(hour_speed)) hour_speed = 0;
        
        $('#profit').html(system.fixed(session_data['profit'], 2));
        $('#repeats-profit').html(system.fixed(session_data['repeats_profit'], 2));
        $('#hour-speed').html(system.fixed(hour_speed, 2));
    });
}, 2000);

setInterval(function() {
    if (!isNaN(getFirstId())) {
        if ($data.ts_job_time === undefined) {
            $data.ts_job_time = system.microtime();
        } else {
            $data.job_time += system.microtime() - $data.ts_job_time;
            $data.ts_job_time = system.microtime();
        }
    } else {
        if ($data.ts_job_time !== undefined) {
            $data.job_time += system.microtime() - $data.ts_job_time;
            $data.ts_job_time = undefined;
        }
    }
}, 250);

setInterval(updateAccounts, 1000);

$(document).keydown(function(e) {
    switch(e.which) {
        case 27: // esc
            var id = getFirstId();
            if (!isNaN(id)) skip(id);
            break;
        case 13: //enter
            $('#input').val($('#input').val() + ' ');
            break;
        case 32: //пробел
            $('#input').val().substring(0, $('#input').val().length - 1);// (old ??? comment) пусть в этом месте будет, все норм робит
            var id = getFirstId();

            if (!isNaN(id)) {
                if ($('#input').val() === '') skip(id);
                else send(id);
            }
            
            setTimeout(function() {$('#input').val('');}, 0);
            break;
        case 119: //f8
            startAll();
            break;
        case 19: //pause
            pauseAll();
            break;
        
    }
});

$(document).keyup(function(e) {
    switch (e.which) {
        case 9://tab
            updateLang();
            break;
    }
});

setTimeout(function getCaptchas() {
    $.get('app/get_captchas', function(json) {
        var captchas_data = $.parseJSON(json);
        
        for (var key in captchas_data) {
            var captcha = captchas_data[key];
            
            if (!$('div').is('#captcha-' + captcha['id'])) {
                if (!getNumCaptchas()) {
                    if (settings.sound) new Audio('sound.mp3').play();
                    $('#input').val('');
                }
                
                settings.position_captcha_first = !settings.position_captcha_first;
                var position_class = (settings.position_captcha_first ? 'first' : 'two');
                
                var types_html = '\n\
                    <table class="types">\n\
                        <tr>\n\
                            <td class="is-reg' + (+captcha['caps'] ? '' : (+captcha['is_reg'] ? ' active' : '')) + '">Регистр</td>\n\
                            <td class="is-phrase' + (+captcha['is_phrase'] ? ' active' : '') + '">Два слова</td>\n\
                            <td class="is-numeric' + (+captcha['is_numeric'] ? ' active' : '') + '">Цифры</td>\n\
                        </tr>\n\
                    </table>';
                
                $('#captchas').append('\n\
                    <div class="captcha ' + position_class + '" id="captcha-' + captcha['id'] + '">\n\
                        <span class="id">' + captcha['id'] + '</span>\n\
                        <div class="active"></div>\n\
                        ' + (settings.position_captcha_first ? types_html : '') + '\n\
                        <div class="image" style="background-image: url(\'' + captcha['base64'] + '\')"></div>\n\
                        ' + (settings.position_captcha_first ? '' : types_html) + '\n\
                        <div class="timer">' + mathTimer(captcha['ts_add_us']) + '</div>\n\
                    </div>');
                
                $data.captchas[captcha['id']] = captcha;
            }
        }
        
        setTimeout(getCaptchas, tm.get_captchas);
    });
}, tm.get_captchas);

//update timers
setInterval(function() {
    $('.captcha').each(function(i,elem) {
        var id = +$(elem).find('.id').html();
        var time = mathTimer($data.captchas[id]['ts_add_us']);

        if (time <= 0) skip(id, true);
        else $(elem).find('.timer').html(time);
    });
}, 1000);

function kek() {
    var res = $("#is_save_repeats").prop("checked");
    $.get("app/set_setting?name=save_repeats&value=" + +res);
}
