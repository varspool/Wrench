<?php
/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require(__DIR__ . '/lib/SplClassLoader.php');

$classLoader = new SplClassLoader('WebSocket', __DIR__ . '/lib');
$classLoader->register();

$server = new \WebSocket\Server('localhost', 8000);

// server settings:
$server->setMaxClients(20);
$server->setCheckOrigin(true);
$server->setAllowedOrigin('foo.lh');
$server->setMaxConnectionsPerIp(5);
$server->setMaxRequestsPerMinute(50);

$server->registerApplication('status', \WebSocket\Application\StatusApplication::getInstance());
$server->registerApplication('demo', \WebSocket\Application\DemoApplication::getInstance());
$server->run();
