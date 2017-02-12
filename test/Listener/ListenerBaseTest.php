<?php

namespace Wrench\Listener;

use Wrench\Test\BaseTest;

/**
 * Payload test
 */
abstract class ListenerBaseTest extends BaseTest
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
