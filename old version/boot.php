<?php
error_reporting(-1);
ini_set('display_errors', 0);
ini_set('date.timezone', 'Etc/GMT-4');
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/log.txt');
set_error_handler('error_catcher');
header('Access-Control-Allow-Origin: *');

$db = new db('localhost', 'root', '', 'cpt3');
$db->set_charset('utf8');
/*
$db2 = new db('localhost', 'root', '', 'cpt4');
$db2->set_charset('utf8');
*/
function error_catcher($code, $msg, $file, $line) {
    error_log($msg);
    throw new Exception();
}

class db extends mysqli {
    function __construct($host, $login, $pass, $base_name) {
        parent::__construct($host, $login, $pass, $base_name);
    }
    
    function query($query_str, $resultmode = MYSQLI_STORE_RESULT) {
        $result = parent::query($query_str, $resultmode);
        if ($this->errno !== 0) {
            $error_str_for_log = "\n>>> MySQL-ошибка:\n$this->error\n>>> Текст запроса:\n$query_str\n----------------------------------------------------------------";
            error_log($error_str_for_log);
            trigger_error($error_str_for_log, E_USER_ERROR);
        }
        return $result;
    }
}

// Functions

//hash,base,is_two,width,height  base_1,base_2,hash_1,hash_2
function get_image_data($base) {
    $is_two = false;
    $hash = md5($base);
    
    $file_name_lite = __DIR__ . '/temp/' . microtime(true);
    $file_name_full = base64_to_file($file_name_lite, $base);
    $size = getimagesize($file_name_full);
    
    
    if ($size[0] === 320 and $size[1] === 50) {
        $is_two = true;
        crop($file_name_full, 0, 0, 160, 50);
        $base_crop_1 = file_to_base64($file_name_full);
        
        
        $file_name_full_2 = base64_to_file($file_name_lite . '_2', $base);
        crop($file_name_full_2, 160, 0, 160, 50);
        $base_crop_2 = file_to_base64($file_name_full_2);
        unlink($file_name_full_2);
        
        $hash_crop_1 = md5($base_crop_1);
        $hash_crop_2 = md5($base_crop_2);
        
        $result_two = [
            'base_1' => $base_crop_1,
            'base_2' => $base_crop_2,
            'hash_1' => $hash_crop_1,
            'hash_2' => $hash_crop_2
        ];
    }
    
    unlink($file_name_full);
    
    $result = [
        'base' => $base,
        'hash' => $hash,
        'is_two' => $is_two,
        'width' => $size[0],
        'height' => $size[1]
    ];
    
    if ($is_two) {
        $result = array_merge($result, $result_two);
    }
    
    return $result;
}

function base64_to_file($file_name, $base64) {
    // $data[ 0 ] == "data:image/png;base64"
    // $data[ 1 ] == <actual base64 string>
    $data = explode( ',', $base64);
    $ex = explode('/', $data[0]);
    $file_name .= '.' . explode(';', $ex[1])[0];
    
    $ifp = fopen( $file_name, 'wb' );
    fwrite( $ifp, base64_decode( $data[ 1 ] ) );
    fclose( $ifp );
    return $file_name; 
}

function file_to_base64($file_name) {
    $type = pathinfo($file_name, PATHINFO_EXTENSION);
    $data_file = file_get_contents($file_name);
    return 'data:image/' . $type . ';base64,' . base64_encode($data_file);
}

function crop($image, $x_o, $y_o, $w_o, $h_o) {
    if (($x_o < 0) || ($y_o < 0) || ($w_o < 0) || ($h_o < 0)) {
        echo "Некорректные входные параметры";
        return false;
    }
    list($w_i, $h_i, $type) = getimagesize($image); // Получаем размеры и тип изображения (число)
    $types = array("", "gif", "jpeg", "png"); // Массив с типами изображений
    $ext = $types[$type]; // Зная "числовой" тип изображения, узнаём название типа
    if ($ext) {
        $func = 'imagecreatefrom'.$ext; // Получаем название функции, соответствующую типу, для создания изображения
        $img_i = $func($image); // Создаём дескриптор для работы с исходным изображением
    } else {
        echo 'Некорректное изображение'; // Выводим ошибку, если формат изображения недопустимый
        return false;
    }
    if ($x_o + $w_o > $w_i) $w_o = $w_i - $x_o; // Если ширина выходного изображения больше исходного (с учётом x_o), то уменьшаем её
    if ($y_o + $h_o > $h_i) $h_o = $h_i - $y_o; // Если высота выходного изображения больше исходного (с учётом y_o), то уменьшаем её
    $img_o = imagecreatetruecolor($w_o, $h_o); // Создаём дескриптор для выходного изображения
    imagecopy($img_o, $img_i, 0, 0, $x_o, $y_o, $w_o, $h_o); // Переносим часть изображения из исходного в выходное
    $func = 'image'.$ext; // Получаем функция для сохранения результата
    return $func($img_o, $image); // Сохраняем изображение в тот же файл, что и исходное, возвращая результат этой операции
}
