<?php

$root = __DIR__ . '/../../..';

if (file_exists($root . '/vendor/autoload.php')) {
    $loader = require $root . '/vendor/autoload.php';
} elseif (file_exists($root . '/../../vendor/autoload.php')) {
    $loader = require $root . '/../../vendor/autoload.php';
} else {
    throw new RuntimeException('Could not find composer autoloader; are the composer dependencies installed?');
}

if ($argc != 2 || !$argv[1] || !is_numeric($argv[1]) || (int)$argv[1] <= 1024) {
    throw new InvalidArgumentException('Invalid port number: supply as first argument');
}

$port = (int)$argv[1];

$server = new Wrench\Server('ws://localhost:' . $port);
$server->registerApplication('echo', new Wrench\Application\EchoApplication());
$server->run();
