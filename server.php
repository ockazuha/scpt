<?php
require_once __DIR__ . '/core/boot.php';

require_once __DIR__ . '/core/classes/socket/Socket.php';
require_once __DIR__ . '/core/classes/socket/MySocket.php';

require_once __DIR__ . '/core/classes/socket/Group.php';
require_once __DIR__ . '/core/classes/socket/ConData.php';
require_once __DIR__ . '/core/classes/socket/Client.php';
require_once __DIR__ . '/core/classes/socket/User.php';

set_time_limit(0);
ob_end_clean();
cfg('is_call_500error', false);

new MySocket(cfg('socket')['server_addr']);
