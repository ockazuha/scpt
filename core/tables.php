<?php
db()->query("CREATE TABLE IF NOT EXISTS users ("
        . "id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
        . "login varchar(32) NOT NULL,"
        . "pass varchar(32) NOT NULL,"
        . "num_user int UNSIGNED NOT NULL UNIQUE,"
        . "is_display bool NOT NULL DEFAULT TRUE,"
        . "is_pause bool NOT NULL DEFAULT TRUE"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8");

db()->query("INSERT IGNORE INTO users SET num_user='1', login='submai6', pass='2opywhtomh'");
db()->query("INSERT IGNORE INTO users SET num_user='2', login='gipnach', pass='gQQBWS5Dhs'");
db()->query("INSERT IGNORE INTO users SET num_user='3', login='monkey201777', pass='FHlQDO5LsQ'");
db()->query("INSERT IGNORE INTO users SET num_user='4', login='Vorishka1', pass='3AWZ2802ar'");
