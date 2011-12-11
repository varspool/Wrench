<?php

error_reporting(E_ALL);

require(__DIR__ . '/lib/SplClassLoader.php');

$classLoader = new SplClassLoader('WebSocket', __DIR__ . '/lib');
$classLoader->register();

$server = new \WebSocket\Server('localhost', 8000);

// server settings:
$server->setCheckOrigin(true);
$server->setAllowedOrigin('foo.lh');
$server->setMaxConnectionsPerIp(5);


$server->registerApplication('demo', \WebSocket\Application\DemoApplication::getInstance());
$server->run();
