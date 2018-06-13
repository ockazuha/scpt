<?php
require_once __DIR__ . '/core/boot.php';

require_once __DIR__ . '/core/classes/socket/Socket.php';
require_once __DIR__ . '/core/classes/socket/MySocket.php';
require_once __DIR__ . '/core/classes/socket/Group.php';
require_once __DIR__ . '/core/classes/socket/ConData.php';
require_once __DIR__ . '/core/classes/socket/Client.php';
require_once __DIR__ . '/core/classes/socket/User.php';

set_time_limit(0);

new MySocket(cfg('socket')['server_addr']);
