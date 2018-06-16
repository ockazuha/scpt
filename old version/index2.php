<?php
$style = '';
if (isset($_GET['a'])) $style='2';
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cpt3</title>
    <link rel="stylesheet" href="main<?=$style?>.css?nc=<?=microtime(1)?>" type="text/css">
</head>
<body>
    <div id="wrap-captchas">
        <div id="captchas"></div>
        
        <div id="lang"></div>
        <input id="input" autofocus="">
    </div>
    
    <div id="accounts">
        <table>
            <!--tr>
                <td>Окно</td>
                <td></td>
                <td></td>
                <td>Капча</td>
                <td>Обновление</td>
                <td>Заголовок</td>
            </tr-->
        </table>
    </div>
    
    <!--div id="management">
        <button onclick="pauseAll()">Стоп</button>
    </div-->
    
    <div id="stats">
        <p>Заработано: <span id="profit">0.00</span>
        <p>Повторы: <span id="repeats-profit">0.00</span>
        <p>Скорость: <span id="hour-speed">0.00</span>
        <p><input id="is_save_repeats" type="checkbox" onclick="kek()">Сохранять в "повторы"
    </div>
    
    <script src="jquery-3.2.1.min.js"></script>
    <script src="main.js?nc=<?=microtime(1)?>"></script>
</body>
</html>
