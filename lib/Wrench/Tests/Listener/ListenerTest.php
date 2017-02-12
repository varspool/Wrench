<?php

namespace Wrench\Tests\Listener;

use Wrench\Tests\Test;

/**
 * Payload test
 */
abstract class ListenerTest extends Test
{
    /**
     * @depends testConstructor
     */
    public function testListen($instance)
    {
        $server = $this->createMock('Wrench\Server');

        $instance->listen($server);
    }

    abstract public function testConstructor();
}
