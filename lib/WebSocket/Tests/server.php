<?php

use WebSocket\Server;

require_once dirname(__FILE__) . '/bootstrap.php';

var_dump($argv[1]);

$server = new Server($argv[1]);
$server->run();