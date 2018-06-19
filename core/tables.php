<?php
db()->query("CREATE TABLE IF NOT EXISTS users ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "login varchar(32) NOT NULL,"
        . "pass varchar(32) NOT NULL,"
        . "num_user int UNSIGNED NOT NULL UNIQUE,"
        . "is_display bool NOT NULL DEFAULT TRUE,"
        . "is_pause bool NOT NULL DEFAULT TRUE"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8");

db()->query("INSERT IGNORE INTO users SET num_user='1', login='submai6', pass='2opywhtomh'");
db()->query("INSERT IGNORE INTO users SET num_user='2', login='gipnach', pass='gQQBWS5Dhs'");
db()->query("INSERT IGNORE INTO users SET num_user='3', login='monkey201777', pass='FHlQDO5LsQ'");
db()->query("INSERT IGNORE INTO users SET num_user='4', login='Vorishka1', pass='3AWZ2802ar'");

db()->query("CREATE TABLE IF NOT EXISTS images ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "base64 MEDIUMTEXT NOT NULL"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8");

db()->query("CREATE TABLE IF NOT EXISTS captchas ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "image_id int UNSIGNED NOT NULL UNIQUE,"
        . "ts_add int UNSIGNED NOT NULL,"
        . "num_user int UNSIGNED NOT NULL,"
        . "is_reg bool NOT NULL,"
        . "is_num bool NOT NULL,"
        . "is_phrase bool NOT NULL,"
        . "url varchar(48) NOT NULL,"
        . "is_skip bool NOT NULL,"
        . "input varchar(32),"//unique = dublicate
        . "bid varchar(10) NOT NULL,"
        . "is_caps bool NOT NULL,"
        . "id_caps int UNSIGNED,"
        . "width smallint UNSIGNED NOT NULL,"
        . "height smallint UNSIGNED NOT NULL,"
        . "mime_type varchar(4) NOT NULL,"
        . "is_only_second_part bool NOT NULL,"
        . "is_time_skip bool NOT NULL,"
        . "hash varchar(40) NOT NULL,"
        . "hash_one varchar(40),"
        . "hash_two varchar(40),"
        . "is_two bool NOT NULL,"
        . "is_job bool NOT NULL,"
        . "job_code smallint,"
        . "job_id int UNSIGNED,"
        . "image_id_one int UNSIGNED,"
        . "image_id_two int UNSIGNED"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8");

db()->query("CREATE TABLE IF NOT EXISTS caps ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "width smallint UNSIGNED NOT NULL,"
        . "height smallint UNSIGNED NOT NULL,"
        . "mime_type varchar(4) NOT NULL,"
        . "count int UNSIGNED NOT NULL" // только на введенных капчах (не скип)
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8");

db()->query("CREATE TABLE IF NOT EXISTS settings ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "name varchar(40) NOT NULL UNIQUE,"
        . "value varchar(255)"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8");

db()->query("INSERT IGNORE INTO settings SET name='is_save_repeats', value='0'");

db()->query("CREATE TABLE IF NOT EXISTS repeats ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "is_skip bool NOT NULL,"
        . "input varchar(32),"
        . "is_reg bool NOT NULL,"
        . "is_num bool NOT NULL,"
        . "is_phrase2 bool NOT NULL,"
        . "hash varchar(40) NOT NULL,"
        . "count int UNSIGNED NOT NULL,"
        . "ts_add int UNSIGNED NOT NULL,"
        . "image_id int UNSIGNED NOT NULL"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8");