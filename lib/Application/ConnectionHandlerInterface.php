<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Payload\Payload;

interface ConnectionHandlerInterface
{
    public function onConnect($connection);
    public function onDisconnect($connection);
}
