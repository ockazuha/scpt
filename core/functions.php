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

/***********************************************************************************
Функция img_resize(): генерация thumbnails
Параметры:
  $src             - имя исходного файла
  $dest            - имя генерируемого файла (тип файла будет JPEG)
  $width, $height  - максимальные ширина и высота генерируемого изображения, в пикселях (по ссылке!)
Необязательные параметры:
  $quality         - качество генерируемого JPEG, по умолчанию - максимальное (100)
***********************************************************************************/
function imgToJPG($src, $dest, &$width, &$height, $quality = 100) {
    if (!file_exists($src))
        return 1; // исходный файля не найден
    $size = getimagesize($src);
    if ($size === false)
        return 2; // не удалось получить параметры файла

        
// Определяем исходный формат по MIME-информации и выбираем соответствующую imagecreatefrom-функцию.
    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    $icfunc = "imagecreatefrom" . $format;
    if (!function_exists($icfunc))
        return 3; // не существует подходящей функции преобразования

        
// Определяем необходимость преобразования размера
    if ($width < $size[0] || $height < $size[1])
        $ratio = min($width / $size[0], $height / $size[1]);
    else
        $ratio = 1;

    $width = floor($size[0] * $ratio);
    $height = floor($size[1] * $ratio);
    $isrc = $icfunc($src);
    $idest = imagecreatetruecolor($width, $height);

    imagecopyresampled($idest, $isrc, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
    imagejpeg($idest, $dest, $quality);
    chmod($dest, 0666);
    imagedestroy($isrc);
    imagedestroy($idest);
    return 0; // успешно
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
