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
