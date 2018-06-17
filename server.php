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

try {
    new MySocket(cfg('socket')['server_addr']);
} catch (Base_Exception $e) {
    echo "An error occurred! The work has been completed.\n";
    throw $e;
}
