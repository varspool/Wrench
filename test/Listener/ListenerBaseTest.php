<?php

namespace Wrench\Listener;

use Wrench\Server;
use Wrench\Test\BaseTest;

/**
 * Payload test
 */
abstract class ListenerBaseTest extends BaseTest
{
    /**
     * @depends testConstructor
     * @param Listener $instance
     * @doesNotPerformAssertions
     */
    public function testListen(Listener $instance)
    {
        $server = $this->createMock(Server::class);
        $instance->listen($server);
    }

    abstract public function testConstructor();
}
