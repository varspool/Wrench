<?php

namespace Wrench\Listener;

class RateLimiterTest extends ListenerBaseTest
{
    public function getClass()
    {
        return 'Wrench\Listener\RateLimiter';
    }

    public function testConstructor()
    {
        $instance = $this->getInstance();
        $this->assertInstanceOfClass($instance, 'No constructor arguments');
        return $instance;
    }

    public function testOnSocketConnect()
    {
        $this->getInstance()->onSocketConnect(null, $this->getConnection());
    }

    public function testOnSocketDisconnect()
    {
        $this->getInstance()->onSocketDisconnect(null, $this->getConnection());
    }

    public function testOnClientData()
    {
        $this->getInstance()->onClientData(null, $this->getConnection());
    }

    protected function getConnection()
    {
        $connection = $this->createMock('\Wrench\Connection');

        $connection
            ->expects($this->any())
            ->method('getIp')
            ->will($this->returnValue('127.0.0.1'));

        $connection
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('abcdef01234567890'));

        $manager = $this->createMock('\Wrench\ConnectionManager');
        $manager->expects($this->any())->method('count')->will($this->returnValue(5));

        $connection
            ->expects($this->any())
            ->method('getConnectionManager')
            ->will($this->returnValue($manager));

        return $connection;
    }
}
