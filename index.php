<?php
require_once __DIR__ . '/core/static_boot.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>scpt</title>
    <link rel="stylesheet" href="/public/css/main.css?<?=VER?>">
</head>

<body>
    <div id="users">
        <div id="man">
            <div class="btns">
                <div class="btn"><button onclick="setStatusAll('is_pause', false)">Старт</button></div><!--
                --><div class="btn"><button onclick="setStatusAll('is_pause', true)">Пауза</button></div><!--
                --><div class="btn"><button onclick="setStatusAll('is_display', true)">Показать</button></div><!--
                --><div class="btn"><button onclick="setStatusAll('is_display', false)">Скрыть</button></div><!--
                --><div class="btn"><button onclick="sock.send('exit')">Стоп</button></div>
            </div>
        </div>
        
        <table>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
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
    
    <input id="input" autofocus="">
    
    <div id="capts">
        
    </div>
    
    <script src="/public/js/jquery-3.3.1.min.js"></script>
    <script src="/get_js_source?name=main_client&<?=VER?>"></script>
</body>
</html>