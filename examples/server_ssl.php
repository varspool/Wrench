#!/usr/bin/env php
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

// Generate PEM file
$pemFile                = __DIR__ . '/generated.pem';
$pemPassphrase          = null;
$countryName            = "DE";
$stateOrProvinceName    = "none";
$localityName           = "none";
$organizationName       = "none";
$organizationalUnitName = "none";
$commonName             = "example.com";
$emailAddress           = "someone@example.com";

Wrench\Util\Ssl::generatePEMFile(
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
$server = new \Wrench\Server('wss://127.0.0.1:8000/', array(
     'connection_manager_options' => array(
         'socket_master_options' => array(
             'server_ssl_cert_file'         => $pemFile,
             'server_ssl_passphrase'        => $pemPassphrase,
             'server_ssl_allow_self_signed' => true,
             'server_ssl_verify_peer'       => false
         )
     )
));

$server->registerApplication('echo', new \Wrench\Application\EchoApplication());
$server->registerApplication('time', new \Wrench\Application\ServerTimeApplication());
$server->run();
