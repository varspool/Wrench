<?php

namespace Wrench\Tests;

use Wrench\Protocol\Protocol;

use Wrench\Connection;
use Wrench\Tests\Test;
use Wrench\Socket;

use \InvalidArgumentException;
use \PHPUnit_Framework_Error;

/**
 * Tests the Connection class
 */
class ConnectionTest extends Test
{
    /**
     * @see Wrench\Tests.Test::getClass()
     */
    protected function getClass()
    {
        return 'Wrench\Connection';
    }

    /**
     * Tests the constructor
     *
     * @dataProvider getValidConstructorArguments
     */
    public function testConstructor($manager, $socket, array $options)
    {
        $this->assertInstanceOfClass(
            $this->getInstance(
                $manager,
                $socket,
                $options
            ),
            'Valid constructor arguments'
        );
    }

    /**
     * @dataProvider getValidCloseCodes
     */
    public function testClose($code)
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
                ->method('getIp')
                ->will($this->returnValue('127.0.0.1'));

        $socket->expects($this->any())
                ->method('getPort')
                ->will($this->returnValue(mt_rand(1025, 50000)));

        $manager = $this->getMockConnectionManager();

        $connection = $this->getInstance($manager, $socket);
        $connection->close($code);
    }

    /**
     * @return ConnectionManager
     */
    protected function getMockConnectionManager()
    {
        return $this->getMock('Wrench\ConnectionManager', array(), array(), '', false);
    }

    /**
     * Gets a mock socket
     *
     * @return Socket
     */
    protected function getMockSocket()
    {
        return $this->getMock('Wrench\Socket\ClientSocket', array(), array('wss://localhost:8000'));
    }

    /**
     * Data provider
     *
     * @return array<array<int>>
     */
    public function getValidCloseCodes()
    {
        $arguments = array();
        foreach (Protocol::$closeReasons as $code => $reason) {
            $arguments[] = array($code);
        }
        return $arguments;
    }

    /**
     * Data provider
     *
     * @return array<array<mixed>>
     */
    public function getValidConstructorArguments()
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
                ->method('getIp')
                ->will($this->returnValue('127.0.0.1'));

        $socket->expects($this->any())
                ->method('getPort')
                ->will($this->returnValue(mt_rand(1025, 50000)));

        $manager = $this->getMockConnectionManager();

        return array(
            array(
                $manager,
                $socket,
                array('logger' => function() {})
            ),
            array(
                $manager,
                $socket,
                array('logger' => function () {})
            )
        );
    }
}
