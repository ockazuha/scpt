// начинает выполняться, когда дом готов, но доп ресурсы могут быть не догружены

// FUNCTIONS

var system = {
    // доли секунды тоже возвращает
    microtime: function() {
        var date = new Date();
        return date.getTime() / 1000;
    },
    
    // делает encode данных
    objToStr: function(obj) {
        var str = '';
    
        for (var key in obj) {
            if (str !== '') str += '&';
            str += key + '=' + encodeURIComponent(obj[key]);
        }

        return str;
    }
};

var http = {
    // get данные в виде строки
    get: function(url, get_data = '') {
        url = url + '?nc=' + system.microtime() + (get_data === '' ? '' : '&' + get_data);
        xhr.open('GET', url, false);
        xhr.send();
        
        if (xhr.status !== 200) {
            alert('Код GET-запроса не равен 200. URL: ' + url);
            debugger;
            location.reload();
        }
        
        return xhr.responseText;
    },
    
    // get данные в виде строки. post данные в виде объекта
    post: function(url, post_data, get_data = '') {
        url = url + '?nc=' + system.microtime() + (get_data === '' ? '' : '&' + get_data);
        post_data = system.objToStr(post_data);
        
        xhr.open('POST', url, false);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(post_data);
        
        if (xhr.status !== 200) {
            alert('Код POST-запроса не равен 200. URL: ' + url + ' POST-данные: ' + post_data);
            debugger;
            location.reload();
        }
        
        return xhr.responseText;
    }
};

// and visible and update 'settings.account'
function updateStatus() {
    var account_data = getAccountData();
    
    if (settings && settings.account) {
        if (settings.account.visible !== account_data.visible) {
            $('body').css({display: (+account_data.visible ? 'block' : 'none')});
        }
    } else {
        $('body').css({display: (+account_data.visible ? 'block' : 'none')});
    }
    
    settings.account = account_data;
}

function getAccountData() {
    return $.parseJSON(http.get('https://localhost/cpt3/us/get_account_data', 'num_chrome_user=' + num_chrome_user));
}

// $('#workArea').css('opacity') строка. если есть капча видимая то 1
//Anti.earn.states.isTaskActive если капча есть или еще ответ не отправился
// Anti.earn.states.requestNewTasks получать новые капчи?

// если есть капча видимая то 1
function getIsOpacity() {
    return +$('#workArea').css('opacity');
}

//  если капча есть или еще ответ не отправился
function getIsTaskActive() {
    return Anti.earn.states.isTaskActive;
}

// получать новые капчи?
function getIsGetNewTasks() {
    return Anti.earn.states.requestNewTasks;
}

function skipTask() {
    Anti.earn.workflow.skipTask();
}

//captchas handling
function captchasHandling() {
    ////console.log('captchasHandling');
    if (+settings.account.pause) {
        if (getIsGetNewTasks()) {
            Anti.earn.interface.playOrPause();
        }
        if (getIsTaskActive()) {
            skipTask(); // во время получения инпута сюда доступа нет
            setTimeout(function checkSkip() {
                ////console.log('#6');
                if (+getIsTaskActive()) {
                    setTimeout(checkSkip, tm.check_skip);
                } else {
                    setTimeout(captchasHandling, tm.cap_hand);
                }
            }, tm.check_skip);
        } else setTimeout(captchasHandling, tm.cap_hand);
        return;
    }
    
    //нет паузы
    //////console.log('нет паузы');
    
    if (!getIsGetNewTasks()) {
        Anti.earn.interface.playOrPause();// могут быть зависоны
        //Anti.earn.init(); // непонятная функция, может пригодится
    }
    
    if (!getIsTaskActive()) {
        setTimeout(captchasHandling, tm.cap_hand);
        return;
    }
    
    if (!getIsOpacity()) {
        setTimeout(captchasHandling, tm.cap_hand);
        return;
    }
    
    ////console.log('есть капча');
    
    var ts_add_captcha = system.microtime();
    var result_add_captcha = $.parseJSON(http.post('https://localhost/cpt3/us/add_captcha', Anti.earn.task, 'account_id=' + settings.account.id + '&ts_add_captcha=' + ts_add_captcha));
    
    
    if (result_add_captcha['is_skip']) {
        ////console.log('капча сразу скипнута');
        skipTask();
        setTimeout(function checkSkip() {
            ////console.log('#1');
            if (+getIsTaskActive()) {
                setTimeout(checkSkip, tm.check_skip);
            } else {
                setTimeout(captchasHandling, tm.cap_hand);
            }
        }, tm.check_skip);
    } else if (result_add_captcha['input'] !== null) {
        ////console.log('на капчу Сразу есть ответ|'+result_add_captcha['input']+'///'+system.microtime());
        enter_captcha(result_add_captcha['input'], true);
        setTimeout(function checkSkip() {
            ////console.log('#2');
            if (+getIsTaskActive()) {
                setTimeout(checkSkip, tm.check_skip);
            } else {
                setTimeout(captchasHandling, tm.cap_hand);
            }
        }, tm.check_skip);
    } else {
        setTimeout(function getInput() {
            ////console.log('getInput');
            if (!getIsTaskActive()) {
                ////console.log('ПРОПАЛА Капча'+system.microtime());
                setTimeout(captchasHandling, tm.cap_hand);
                return;
            }
            
            if ((system.microtime() - ts_add_captcha) >= 34) {
                ////console.log('истекло время получения инпута'+system.microtime());
                skipTask();
                setTimeout(function checkSkip() {
                    ////console.log('#3');
                    if (+getIsTaskActive()) {
                        setTimeout(checkSkip, tm.check_skip);
                    } else {
                        setTimeout(captchasHandling, tm.cap_hand);
                    }
                }, tm.check_skip);
                return;
            }
            
            var result_input = $.parseJSON(http.get('https://localhost/cpt3/us/get_input', 'id=' + result_add_captcha['id']));
            
            if (+result_input['is_skip']) {
                ////console.log('капча скипнута по get_input'+system.microtime());
                skipTask();
                setTimeout(function checkSkip() {
                    ////console.log('#4');
                    if (+getIsTaskActive()) {
                        setTimeout(checkSkip, tm.check_skip);
                    } else {
                        setTimeout(captchasHandling, tm.cap_hand);
                    }
                }, tm.check_skip);
            } else if (result_input['input'] === null) {
                setTimeout(getInput, tm.get_input);
            } else {
                ////console.log('на капчу есть ответ по get_input|'+result_input['input']+'///'+system.microtime());
                enter_captcha(result_input['input']);
                setTimeout(function checkSkip() {
                    ////console.log('#5');
                    if (+getIsTaskActive()) {
                        setTimeout(checkSkip, tm.check_skip);
                    } else {
                        setTimeout(captchasHandling, tm.cap_hand);
                    }
                }, tm.check_skip);
            }
        }, 500);
    }
}

function enter_captcha(input, repeat = false) {
    $('#guesstext').val(input);
    Anti.earn.processor.type0.save();
    set_profit(repeat);
}

function set_profit(repeat = false) {
    var bonus = 1;
    switch (+Anti.earn.statisticsData.ratingLevel) {
        case 1: bonus+=0.02; break;
        case 2: bonus+=0.05; break;
        case 3: bonus+=0.08; break;
        case 4: bonus+=0.10; break;
        case 5: bonus+=0.12; break;
        case 6: bonus+=0.14; break;
        case 7: bonus+=0.15; break;
        case 8: bonus+=0.16; break;
        case 9: bonus+=0.17; break;
        case 10: bonus+=0.18; break;
        case 11: bonus+=0.20; break;
        case 12: bonus+=0.25; break;
    }
    
    http.get('https://localhost/cpt3/us/set_profit', 'value=' + (parseFloat(Anti.earn.task.bid)*bonus));
    if (repeat) http.get('https://localhost/cpt3/us/set_repeats_profit', 'value=' + (parseFloat(Anti.earn.task.bid)*bonus));
    
    http.get('https://localhost/cpt3/us/set_profit_stat', 'value=' + (parseFloat(Anti.earn.task.bid)*bonus));
}

// ЗАГЛУШКИ
//var xhr,num_chrome_user, Anti;

var settings = {
    //account (все строки из таблицы)
};

var $data = {
    
};

var tm = {
    cap_hand: 250,
    get_input: 250,
    check_skip: 250
};

function updateIntervals() {
    tm.cap_hand = +http.get("https://localhost/cpt3/app/get_setting", 'name=tm_cap_hand');
    tm.get_input = +http.get("https://localhost/cpt3/app/get_setting", 'name=tm_get_input');
    tm.check_skip = +http.get("https://localhost/cpt3/app/get_setting", 'name=tm_check_skip');
}

updateIntervals();
setInterval(updateIntervals, 10000);



updateStatus();
$('.flags-list').remove();

setInterval(updateStatus, 1500);

// set maxwaittime
setInterval(function() {
    if (document.title === 'KB Earn') Anti.earn.timers.maxWaitTime = 37000;
}, 4000);

setTimeout(function auth() {
    if (document.title === 'KB Login') {
        $('#enterlogin').val(settings.account.login);
        $('#password').val(settings.account.pass);
        Anti.entrance.loginAttempt();
        
        setTimeout(function checkAuth() {
            if (document.title === 'Start Page') {
                setTimeout(auth, 1500);
            } else {
                setTimeout(checkAuth, 1000);
            }
        }, 1000);
    } else {
        setTimeout(auth, 1500);
    }
}, 1500);

setTimeout(function earn() {
    if (+settings.account.pause) {
        setTimeout(earn, 1500);
    } else {
        if (document.title !== 'KB Earn' && document.title !== 'KB Login' && document.title !== 'Ваши ошибки ввода' && document.title !== 'Слишком много пропусков капч') {
            Anti.navigate('earn');

            setTimeout(function checkEarn() {
                if (document.title === 'KB Earn') {
                    setTimeout(earn, 1500);
                } else {
                    setTimeout(checkEarn, 1000);
                }
            }, 1000);
        } else {
            setTimeout(earn, 1500);
        }
    }
}, 1500);

//start captchasHandling
setTimeout(function checkInit() {
    if (window && window.$$$) {
        setTimeout(captchasHandling, tm.cap_hand);
    } else {
        setTimeout(checkInit, 250);
    }
}, 250);

//update info account
setInterval(function() {
    var post_data = {
        title: document.title,
        ts_last_update_us: system.microtime()
    };
    
    if (getIsTaskActive() && getIsOpacity()) {
        post_data.ts_last_captcha_us = system.microtime();
    }
    
    http.post('https://localhost/cpt3/us/set_account_data', post_data, 'num_chrome_user=' + num_chrome_user);
}, 1000);

