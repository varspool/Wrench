<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Payload\Payload;

interface ConnectionHandlerInterface
{
    public function onConnect(Connection $connection): void;
    public function onDisconnect(Connection $connection): void;
}
