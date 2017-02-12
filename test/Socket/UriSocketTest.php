<?php

namespace Wrench\Socket;

abstract class UriSocketTest extends SocketBaseTest
{
    /**
     * By default, the socket has not required arguments
     */
    public function testConstructor()
    {
        $instance = $this->getInstance('ws://localhost:8000');
        $this->assertInstanceOfClass($instance);
        return $instance;
    }

    /**
     * @dataProvider getInvalidConstructorArguments
     * @expectedException InvalidArgumentException
     */
    public function testInvalidConstructor($uri)
    {
        $this->getInstance($uri);
    }

    /**
     * @depends testConstructor
     */
    public function testGetIp($instance)
    {
        $this->assertStringStartsWith('localhost', $instance->getIp(), 'Correct host');
    }

    /**
     * @depends testConstructor
     */
    public function testGetPort($instance)
    {
        $this->assertEquals(8000, $instance->getPort(), 'Correct port');
    }

    /**
     * Data provider
     */
    public function getInvalidConstructorArguments()
    {
        return [
            [false],
            ['http://www.google.com/'],
            ['ws:///'],
            [':::::'],
        ];
    }
}
