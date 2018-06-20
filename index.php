<?php
require_once __DIR__ . '/core/static_boot.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>scpt</title>
    <link rel="stylesheet" href="public/css/main.css?<?=VER?>">
</head>

<body>
    <div id="settings">
        <input type="checkbox" id="is_save_repeats"><label for="is_save_repeats">Сохранение</label>
    </div>
    
    <div id="users">
        <div id="man">
            <div class="btns">
                <div class="btn"><button onclick="setStatusAll('is_display', true)">Показать</button></div><!--
                --><div class="btn"><button onclick="setStatusAll('is_display', false)">Скрыть</button></div><!--
                --><div class="btn"><button onclick="setStatusAll('is_pause', false)">Старт</button></div><!--
                --><div class="btn"><button onclick="setStatusAll('is_pause', true)">Пауза</button></div><!--
                --><div class="btn"><button onclick="sock.send('exit')">Стоп</button></div>
            </div>
        </div>
        
        <table>
            <tr>
                <td></td>
                <td class="profit text_right"></td>
                <td class="profit_session text_right"></td>
                <td class="speed_hour text_right"></td>
                <td>Сумма</td>
                <td>Баланс</td>
                <td>Аккум</td>
                <td>От мод</td>
                <td>Приоритет</td>
                <td>Уровень</td>
                <td>Вводов</td>
                <td>Скипов</td>
                <td></td>
            </tr>
        </table>
    </div>
    
    
    
    <div id="capts_wrap">
        <input id="input" autofocus="">
        
        <div id="capts">

        </div>
    </div>
    
    <div id="ented">
        
    </div>
    
    <div id="repeats">
        
    </div>
    
    <script src="public/js/jquery-3.3.1.min.js"></script>
    <script src="get_js_source?name=main_client&<?=VER?>"></script>
</body>
</html>