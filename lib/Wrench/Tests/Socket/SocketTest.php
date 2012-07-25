<?php

namespace Wrench\Tests\Socket;

use Wrench\Tests\Test;
use \Exception;

abstract class SocketTest extends Test
{
    /**
     * Require constructor testing
     */
    abstract public function testConstructor();

    /**
     * @depends testConstructor
     */
    public function testIsConnected($instance)
    {
        $connected = $instance->isConnected();
        $this->assertTrue(is_bool($connected), 'isConnected returns boolean');
        $this->assertFalse($connected);
    }
}