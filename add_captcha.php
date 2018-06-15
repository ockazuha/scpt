<?php
require_once __DIR__ . '/../boot.php';
$is_two = false;

$post =& $_POST;
$caps = false;
// логирование скорости нужно

$post['is_reg'] = (int)(bool)$post['is_reg'];
$post['is_phrase'] = (int)(bool)$post['is_phrase'];
$post['is_numeric'] = (int)(bool)$post['is_numeric'];

$same_hash = md5($post['body']) . $post['is_reg'] . $post['is_phrase'] . $post['is_numeric']; // для same capt + keys

// примитивно сделал. лучше еще проверка по размеру капчи, сортировка и лимит и сразу проверку инпута на существующий.
$same_captcha = $db->query("SELECT id FROM captchas WHERE same_hash='$same_hash' and ts_add_us >= '" . ((int)(microtime(true)-50)) . "'")->fetch_assoc();
if ($same_captcha) {
    $result = [
        'id' => $same_captcha['id'],
        'is_skip' => false,
        'input' => null
    ];

    exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}

//hash,base,is_two,width,height  base_1,base_2,hash_1,hash_2

$image_data = get_image_data($post['body']);
$post_keys = $post['is_reg'] . ($image_data['is_two'] ? '0' : $post['is_phrase']) . $post['is_numeric'];

if ($image_data['width'] === 280 and $image_data['height'] === 88) {
    $caps = true;
}

if ($post_keys === '011' or $post_keys === '111') {
    $result = [
        'is_skip' => true,
        'input' => null
    ];
    
    exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}

function get_repeat_data($hash, $keys) {
    global $db;
    $str = "SELECT * FROM repeats WHERE repeat_hash='$hash'";
    
    switch ($keys) {
        case '000':
            $str .= " and is_numeric=0 and is_phrase=0";
            break;
        case '001':
            $str .= " and (is_numeric=1 or is_numeric_2=1)";
            break;
        case '010':
            $str .= " and (is_phrase=1 or is_phrase_2=1)";
            break;
        case '100':
            $str .= " and is_reg=1 and is_numeric=0 and is_phrase=0";
            break;
        case '110':
            $str .= " and is_reg=1 and (is_phrase=1 or is_phrase_2=1)";
            break;
    }
    
    return $db->query($str)->fetch_assoc();
}


if ($image_data['is_two']) {
    $is_two = true;
    $repeat_hash_1 = $image_data['hash_1'] . $image_data['width'] . $image_data['height'];
    $repeat_hash_2 = $image_data['hash_2'] . $image_data['width'] . $image_data['height'];
    
    $repeat_data_1 = get_repeat_data($repeat_hash_1, $post_keys);
    $repeat_data_2 = get_repeat_data($repeat_hash_2, $post_keys);
    
    
    if ($repeat_data_1 and $repeat_data_2) {
        $db->query("UPDATE repeats SET count = count + 1 WHERE id='$repeat_data_1[id]'");
        $db->query("UPDATE repeats SET count = count + 1 WHERE id='$repeat_data_2[id]'");
        //1и2
        if ($repeat_data_1['is_skip'] and $repeat_data_2['is_skip']) {
            //обе скип
            $result = [
                'is_skip' => true,
                'input' => null
            ];
        } elseif ($repeat_data_1['is_skip']) {
            //скип только 1
            $result = [
                'is_skip' => false,
                'input' => ($post['is_phrase'] ? ' ' : '') . $repeat_data_2['input']
            ];
        } elseif ($repeat_data_2['is_skip']) {
            //скип только 2
            $result = [
                'is_skip' => false,
                'input' => $repeat_data_1['input'] . ($post['is_phrase'] ? ' ' : '')
            ];
        } else {
            // не скипнуты
            $result = [
                'is_skip' => false,
                'input' => $repeat_data_1['input'] . ' ' . $repeat_data_2['input']
            ];
        }
        
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    } elseif ($repeat_data_1) {
        $db->query("UPDATE repeats SET count = count + 1 WHERE id='$repeat_data_1[id]'");
        //1ч
        $job_code = ($repeat_data_1['is_skip'] ? ($post['is_phrase'] ? 10 : 9) : ($post['is_phrase'] ? 8 : 7));
        
        foreach ($post as &$value) {
            $value = $db->escape_string($value);
        }
        unset($value);
        $base64 = $db->escape_string($image_data['base_2']);

        $db->query("BEGIN");
        
        $db->query("INSERT INTO images SET "
                . "base64='$base64',"
                . "is_numeric='$post[is_numeric]',"
                //. "is_phrase='$post[is_phrase]'," требовалось удалить.
                . "is_reg='$post[is_reg]',"
                . "url='$post[url]'");

        $image_id = $db->insert_id;

        $db->query("INSERT INTO captchas SET "
                . "image_id='$image_id',"
                . "ts_add_us='$_GET[ts_add_captcha]',"
                . "account_id='$_GET[account_id]',"
                . "same_hash='$same_hash',"
                . "is_two='$is_two',"
                . "caps='" . (int)$caps . "',"
                . "job_code='$job_code'" . ($repeat_data_1['is_skip'] ? "" : ", job_repeat_id='$repeat_data_1[id]'"));

        $captcha_id = $db->insert_id;
        
        $db->query("COMMIT");

        $result = [
            'id' => $captcha_id,
            'is_skip' => false,
            'input' => null
        ];
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    } elseif ($repeat_data_2) {
        $db->query("UPDATE repeats SET count = count + 1 WHERE id='$repeat_data_2[id]'");
        //2ч
        $job_code = ($repeat_data_2['is_skip'] ? ($post['is_phrase'] ? 4 : 3) : ($post['is_phrase'] ? 6 : 5));
        
        foreach ($post as &$value) {
            $value = $db->escape_string($value);
        }
        unset($value);
        $base64 = $db->escape_string($image_data['base_1']);

        $db->query("BEGIN");
        
        $db->query("INSERT INTO images SET "
                . "base64='$base64',"
                . "is_numeric='$post[is_numeric]',"
                //. "is_phrase='$post[is_phrase]'," требовалось удалить.
                . "is_reg='$post[is_reg]',"
                . "url='$post[url]'");

        $image_id = $db->insert_id;

        $db->query("INSERT INTO captchas SET "
                . "image_id='$image_id',"
                . "ts_add_us='$_GET[ts_add_captcha]',"
                . "account_id='$_GET[account_id]',"
                . "same_hash='$same_hash',"
                . "is_two='$is_two',"
                . "caps='" . (int)$caps . "',"
                . "job_code='$job_code'" . ($repeat_data_2['is_skip'] ? "" : ", job_repeat_id='$repeat_data_2[id]'"));

        $captcha_id = $db->insert_id;
        
        $db->query("COMMIT");

        $result = [
            'id' => $captcha_id,
            'is_skip' => false,
            'input' => null
        ];
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    } else {
        foreach ($post as &$value) {
            $value = $db->escape_string($value);
        }
        unset($value);
        
        $db->query("BEGIN");

        $db->query("INSERT INTO images SET "
                . "base64='$post[body]',"
                . "is_numeric='$post[is_numeric]',"
                . "is_phrase='$post[is_phrase]',"
                . "is_reg='$post[is_reg]',"
                . "url='$post[url]'");

        $image_id = $db->insert_id;

        $db->query("INSERT INTO captchas SET "
                . "image_id='$image_id',"
                . "ts_add_us='$_GET[ts_add_captcha]',"
                . "account_id='$_GET[account_id]',"
                . "same_hash='$same_hash',"
                . "is_two='$is_two',"
                . "caps='" . (int)$caps . "',"
                . "job_code='" . ($post['is_phrase'] ? 2 : 1) . "'");

        $captcha_id = $db->insert_id;
        
        $db->query("COMMIT");

        $result = [
            'id' => $captcha_id,
            'is_skip' => false,
            'input' => null
        ];
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
} else {
    $repeat_hash = $image_data['hash'] . $image_data['width'] . $image_data['height'];
    $repeat_data = get_repeat_data($repeat_hash, $post_keys);
    
    if ($repeat_data) {
        $db->query("UPDATE repeats SET count = count + 1 WHERE id='$repeat_data[id]'");
        
        $result = [
            'is_skip' => (bool)$repeat_data['is_skip'],
            'input' => $repeat_data['input']
        ];
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}



foreach ($post as &$value) {
    $value = $db->escape_string($value);
}
unset($value);

$db->query("BEGIN");

$db->query("INSERT INTO images SET "
        . "base64='$post[body]',"
        . "is_numeric='$post[is_numeric]',"
        . "is_phrase='$post[is_phrase]',"
        . "is_reg='$post[is_reg]',"
        . "url='$post[url]'");

$image_id = $db->insert_id;

$db->query("INSERT INTO captchas SET "
        . "image_id='$image_id',"
        . "ts_add_us='$_GET[ts_add_captcha]',"
        . "account_id='$_GET[account_id]',"
        . "same_hash='$same_hash',"
        . "caps='" . (int)$caps . "',"
        . "is_two='$is_two'"); // для same capt + keys

$captcha_id = $db->insert_id;

$db->query("COMMIT");

// при скиповании или вводе Можно добавить данные в инпут таблицу

$result = [
    'id' => $captcha_id,
    'is_skip' => false,
    'input' => null
];

echo json_encode($result, JSON_UNESCAPED_UNICODE);

