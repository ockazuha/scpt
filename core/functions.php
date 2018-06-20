<?php
//можно: изменять ячейку массива или перезаписывать объект + запросить ячейку массива по ключу.
//Возвращается ячейка массива или объект (при инициализации тоже, но при ней нельзя указать ключ массива)
function varToFunc($name, $init_data) {
    eval('function ' . $name . '($key = null, $new_value = null, $init_data = null) {'
            . 'static $data;'
            . 'if (isset($init_data)) $data = $init_data;'
            . 'if (isset($new_value)) {'
            . 'if (is_array($data)) $data[$key] = $new_value;'
            . 'else $data = $new_value;'
            . '}'
            . 'if (isset($key)) return $data[$key];'
            . 'else return $data;'
            . '}');
    return $name(null, null, $init_data);
}

function jsonEncode($str) {
    return json_encode($str, JSON_UNESCAPED_UNICODE);
}

function obEndContents() {
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
}

function varDump($var) {
    ob_start();
    var_dump($var);
    return obEndContents();
}

function clearNullByte($str) {
    return preg_replace('/\0/s', '', $str);
}

function imgToJPG($input_file, $output_file, $imagesize = null, $new_size = null, $quality = 90) {
    if (!file_exists($input_file)) return 1;
    if (!isset($imagesize)) $imagesize = getimagesize($input_file);
    if ($imagesize === false) return 2;
    
    $width = $imagesize[0];
    $height = $imagesize[1];
    
    $mime = strtolower(substr($imagesize['mime'], strpos($imagesize['mime'], '/') + 1));
    $func = "imagecreatefrom{$mime}";
    if (!function_exists($func)) return 3;
    
    if (isset($new_size)) {
        if ($new_size[0] < $width || $new_size[1] < $height) {
            $ratio = min($new_size[0] / $width, $new_size[1] / $height);
        } else {
            $ratio = 1;
        }

        $new_width = floor($width * $ratio);
        $new_height = floor($height * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    $input = $func($input_file);
    $output = imagecreatetruecolor($new_width, $new_height);
    $resize = imagecreatetruecolor($new_width, $new_height);
    
    $trans = ($mime === 'png' or $mime === 'gif');
    
    if ($trans) {
        imagealphablending($resize, false);
        imagesavealpha($resize, true);
    }
    
    imagecopyresampled($resize, $input, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    if ($trans) {
        $white = imagecolorallocate($output,  255, 255, 255);
        imagefilledrectangle($output, 0, 0, $new_width, $new_height, $white);
        imagecopy($output, $resize, 0, 0, 0, 0, $new_width, $new_height);
        
        imagejpeg($output, $output_file, $quality);
    } else {
        imagejpeg($resize, $output_file, $quality);
    }
    
    return 0;
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

function base64ToFile($file_name, $base64) {
    // $data[ 0 ] == "data:image/png;base64"
    // $data[ 1 ] == <actual base64 string>
    $data = explode( ',', $base64);
    $ex = explode('/', $data[0]);
    $mime = explode(';', $ex[1])[0];
    $file_name .= '.' . $mime;
    
    $ifp = fopen( $file_name, 'wb' );
    fwrite( $ifp, base64_decode( $data[ 1 ] ) );
    fclose( $ifp );
    return [$file_name, $mime];
}

function fileToBase64($file_name) {
    $type = pathinfo($file_name, PATHINFO_EXTENSION);
    $data_file = file_get_contents($file_name);
    return 'data:image/' . $type . ';base64,' . base64_encode($data_file);
}
