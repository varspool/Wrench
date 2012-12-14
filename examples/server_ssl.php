<?php
/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require(__DIR__ . '/../lib/SplClassLoader.php');

$classLoader = new SplClassLoader('Wrench', __DIR__ . '/../lib');
$classLoader->register();

// Generate PEM file
$pemFile                = dirname(__FILE__) . '/generated.pem';
$pemPassphrase          = null;
$countryName            = "DE";
$stateOrProvinceName    = "none";
$localityName           = "none";
$organizationName       = "none";
$organizationalUnitName = "none";
$commonName             = "foo.lh";
$emailAddress           = "baz@foo.lh";

\Wrench\Socket::generatePEMFile(
    $pemFile,
    $pemPassphrase,
    $countryName,
    $stateOrProvinceName,
    $localityName,
    $organizationName,
    $organizationalUnitName,
    $commonName,
    $emailAddress
);

// User can use tls in place of ssl
$server = new \Wrench\Server('127.0.0.1', 8000, 'ssl', $pemFile, $pemPassphrase);

// server settings:
$server->setMaxClients(100);
$server->setCheckOrigin(true);
$server->setAllowedOrigin('foo.lh');
$server->setMaxConnectionsPerIp(100);
$server->setMaxRequestsPerMinute(2000);

// Hint: Status application should not be removed as it displays usefull server informations:
$server->registerApplication('status', \Wrench\Application\StatusApplication::getInstance());
$server->registerApplication('demo', \Wrench\Application\DemoApplication::getInstance());

$server->run();