<?php

namespace WebSocket\Tests\Protocol;

use WebSocket\Protocol\Rfc6455Protocol;
use WebSocket\Tests\Protocol\ProtocolTest;

class Rfc6455ProtocolTest extends ProtocolTest
{
    protected function getClass()
    {
        return 'WebSocket\Protocol\Rfc6455Protocol';
    }
}