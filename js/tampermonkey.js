// ==UserScript==
// @name         scpt
// @namespace    http://tampermonkey.net/
// @version      0.1
// @description  try to take over the world!
// @author       ockazuha
// @match        https://kolotibablo.com/workers/*
// @grant        none
// @noframes
// ==/UserScript==

(function() {
    'use strict';

    var userscript = {
        domain: 'localhost/scpt',
        num_user: 1,
        xhr: new XMLHttpRequest(),
        
        getJSSource: function(name) {
            this.xhr.open('GET', 'https://' + this.domain + '/get_js_source?name=' + name + '&' + Math.random(), false);
            this.xhr.send();
            return this.xhr.responseText;
        }
    };
    
    eval(userscript.getJSSource('main_userscript'));
})();
