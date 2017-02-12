<?php

namespace Wrench\Socket;

class ServerClientSocketTest extends SocketBaseTest
{
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
     * @expectedException \Wrench\Exception\SocketException
     * @depends testConstructor
     */
    public function testGetIpTooSoon($instance)
    {
        $instance->getIp();
    }

    /**
     * @expectedException \Wrench\Exception\SocketException
     * @depends testConstructor
     */
    public function testGetPortTooSoon($instance)
    {
        $instance->getPort();
    }
}
