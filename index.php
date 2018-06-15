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
    <div id="man">
        <button onclick="sock.send('exit')">Остановить сервер</button>
        <button onclick="setStatusAll('is_display', true)">Показать все</button>
        <button onclick="setStatusAll('is_display', false)">Скрыть все</button>
        <button onclick="setStatusAll('is_pause', false)">Старт все</button>
        <button onclick="setStatusAll('is_pause', true)">Пауза все</button>
    </div>
    
    <div id="users">
        <table>
            <thead>
                <th>Окно</th>
                <th>Дисплей</th>
                <th>Пауза</th>
            </thead>
        </table>
    </div>
    
    <input id="input" autofocus="">
    
    <div id="capts">
        
    </div>
    
    <script src="/public/js/jquery-3.3.1.min.js"></script>
    <script src="/get_js_source?name=main_client&<?=VER?>"></script>
</body>
</html>