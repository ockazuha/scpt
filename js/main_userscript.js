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
    
    skip: function(id) {
        log('skip');
        Anti.earn.workflow.skipTask();
        
        checkSkip(id);
    },
    
    startStop: function() {
        log('start stop');
        Anti.earn.interface.playOrPause();
    }
};

function checkSkip(id) {
    sett.is_stop_cpt = false;
    
    setTimeout(function checkSkip() {
        log('check skip');

        if ((!ant.isTask() || Anti.earn.task.id !== id)) {
            sett.is_check_next_input = false;
            start();
        } else {
            setTimeout(checkSkip, sett.t_check_skip);
        }
    }, sett.t_check_skip);
}

var sett = {
    t_cpt: 100,
    max_time: 17,
    t_check_skip: 100,
    is_log: true,
    max_wait_time: 20000,
    is_stop_cpt: false,
    is_check_next_input: false
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
        case 'input':
            sett.is_stop_cpt = true;
            
            setTimeout(function checkStopCpt() {
                if (sett.is_check_next_input) {
                    $('#guesstext').val(data);
                    Anti.earn.processor.type0.save();
                    dat.is_get_input = false;
                    checkSkip(Anti.earn.task.id);
                } else {
                    setTimeout(checkStopCpt, 100);
                }
            }, 100);
            
            
            break;
        case 'skip':
            sett.is_stop_cpt = true;
            
            setTimeout(function checkStopCpt() {
                if (sett.is_check_next_input) {
                    dat.is_get_input = false;
                    ant.skip(Anti.earn.task.id);
                } else {
                    setTimeout(checkStopCpt, 100);
                }
            }, 100);
            
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
    if (sett.is_stop_cpt) {
        sett.is_check_next_input = true;
        return;
    }
    //log('cpt');
    if (dat.is_get_input) {
        if ((func.microtime() - dat.ts_capt) >= sett.max_time) {
            log('истекло время');
            dat.is_get_input = false;
            ant.skip(Anti.earn.task.id);
            return;
        }
    }
    //log('1');
    if (user.is_pause) {
        if (ant.isGetTasks()) ant.startStop();
    } else {
        if (!ant.isGetTasks()) ant.startStop();
    }
    
    if (!(ant.isTask() && ant.isOpacity())) {
        start();
        return;
    }
   
    //log('4');
    if (dat.is_get_input) {
        //log('ожидание инпута');
        start();
        return;
    }
    log('5');
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