<?php

namespace Wrench\Tests\Socket;

class ServerClientSocketTest extends SocketTest
{
    public function getClass()
    {
        return 'Wrench\Socket\ServerClientSocket';
    }

    /**
     * By default, the socket has not required arguments
     */
    public function testConstructor()
    {
        $resource = null;
        $instance = $this->getInstance($resource);
        $this->assertInstanceOfClass($instance);
        return $instance;
    }

    /**
     * @depends testConstructor
     */
    public function testGetIpTooSoon($instance)
    {
        $this->expectException(\Wrench\Exception\SocketException::class);

        $instance->getIp();
    }

    /**
     * @depends testConstructor
     */
    public function testGetPortTooSoon($instance)
    {
        $this->expectException(\Wrench\Exception\SocketException::class);

        $instance->getPort();
    }
}
