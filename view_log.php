<?php
define('OFF_ERROR_HANDLING', true);
require_once __DIR__ . '/core/static_boot.php';

$text = file_get_contents(cfg('error_log'));

$str1 = "\n>>> Trace: ";
$str2 = "\n";

$fpos = function($pos = 0) use (&$text, $str1) {
    return mb_strpos($text, $str1, $pos);
};

$pos = $fpos();

while ($pos !== false) {
    $pos += mb_strlen($str1);
    $pos2 = mb_strpos($text, $str2, $pos);
    $str = mb_substr($text, $pos, $pos2-$pos);
    // почему то берется вместе с $str2. делаю trim
    $str = trim($str);
    $str_decode = base64_decode($str);
    //gzdecode плюется ошибками
    $str_decode = @gzdecode($str_decode);
    $text = str_replace($str, $str_decode, $text);
    $pos = $pos2 + mb_strlen($str2);
    $pos = $fpos($pos);
}

echo "<plaintext>$text";