var sock, json, func;
eval(userscript.getJSSource('functions'));
eval(userscript.getJSSource('socket'));

var user = {
    //is_display,is_pause,login,pass
};

var ant = {
    isOpacity: function() {
        return func.bool($('#workArea').css('opacity'));
    },
    
    isTask: function() {
        return func.bool(Anti.earn.states.isTaskActive);
    },
    
    isGetTasks: function() {
        return func.bool(Anti.earn.states.requestNewTasks);
    },
    
    skip: function() {
        log('skip');
        Anti.earn.workflow.skipTask();
        
        setTimeout(function checkSkip() {
            log('check skip');
            if (ant.isTask()) {
                setTimeout(checkSkip, sett.t_check_skip);
            } else {
                start();
            }
        }, sett.t_check_skip);
    },
    
    startStop: function() {
        log('start stop');
        Anti.earn.interface.playOrPause();
    }
};

var sett = {
    t_cpt: 500,
    max_time: 17,
    t_check_skip: 500,
    is_log: true,
    max_wait_time: 20000
};

var dat = {
    is_get_input: false,
    //ts_capt
};

sock.init('<?=cfg('socket')['client_addr']?>', 'users', userscript.num_user, function(cmd, data) {
    switch (cmd) {
        case 'init':
            sock.send('get_user');
            break;
        case 'set_status':
            data = json.decode(data);
            user[data[0]] = func.bool(data[1]);
            
            if (data[0] === 'is_display') {
                setDisplay();
            }
            break;
        case 'user': // ВЫПОЛНЯТЬ РАЗОВО
            data = json.decode(data);
            user = data;
            user.is_display = func.bool(user.is_display);
            user.is_pause = func.bool(user.is_pause);
            
            setDisplay();
            
            setTimeout(auth, 1000);
            setTimeout(earn, 1000);
            
            setInterval(function() {
                if (checkTitle('KB Earn')) Anti.earn.timers.maxWaitTime = sett.max_wait_time;
            }, 4000);
            
            start();
            break;
    }
});

function setDisplay() {
    $('body').css({display: (user.is_display ? 'block' : 'none')});
}

function checkTitle(val) {
    if (document.title.indexOf(val) !== -1) return true;
    return false;
}

function auth() {
    if (checkTitle('KB Login')) {
        $('#enterlogin').val(user.login);
        $('#password').val(user.pass);
        Anti.entrance.loginAttempt();

        setTimeout(function checkAuth() {
            if (checkTitle('Start Page')) {
                setTimeout(auth, 1000);
            } else {
                if ($('#captchaText').css('display') === 'inline-block') {
                    $('#captchaText').focus();
                }
                setTimeout(checkAuth, 1000);
            }
        }, 1000);
    } else {
        setTimeout(auth, 1000);
    }
}

function earn() {
    if (user.is_pause) {
        setTimeout(earn, 1000);
    } else {
        if (checkTitle('KB Earn')
            || checkTitle('KB Login')
            || checkTitle('Ваши ошибки ввода')
            || checkTitle('Слишком много пропусков капч')
        ) {
            setTimeout(earn, 1000);
        } else {
            Anti.navigate('earn');

            setTimeout(function checkEarn() {
                if (checkTitle('KB Earn')) {
                    setTimeout(earn, 1000);
                } else {
                    setTimeout(checkEarn, 1000);
                }
            }, 1000);
        }
    }
}

function cpt() {
    log('cpt');
    if (dat.is_get_input) {
        if ((func.microtime() - dat.ts_capt) >= sett.max_time) {
            log('истекло время');
            dat.is_get_input = false;
            ant.skip();
            return;
        }
    }
    
    if (user.is_pause) {
        if (ant.isGetTasks()) ant.startStop();
        start();
        return;
    }
    
    if (!ant.isGetTasks()) ant.startStop();
    
    if (!ant.isTask() || !ant.isOpacity()) {
        log('капчи нет');
        dat.is_get_input = false;
        start();
        return;
    }
    
    if (dat.is_get_input) {
        log('ожидание инпута');
        start();
        return;
    }
    
    log('добавление капчи');
    
    var ts_capt = func.microtime();
    var t = Anti.earn.task;
    
    var capt_data = {
        is_reg: t.is_reg,
        is_phrase: t.is_phrase,
        is_num: t.is_numeric,
        base64: t.body,
        url: t.url,
        ts_add: ts_capt
    };
    
    dat.is_get_input = true;
    dat.ts_capt = ts_capt;
    sock.send('capt', capt_data, true);
    start();
}

function start() {
    setTimeout(cpt, sett.t_cpt);
}

function log(str) {
    if (sett.is_log) {
        console.log('LOG: ' + str);
    }
}