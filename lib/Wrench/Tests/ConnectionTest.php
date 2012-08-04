<?php

namespace Wrench\Tests;

use Wrench\Application\EchoApplication;

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
            $instance = $this->getInstance(
                $manager,
                $socket,
                $options
            ),
            'Valid constructor arguments'
        );

        return $instance;
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
     * @dataProvider getValidHandshakeData
     */
    public function testHandshake($path, $request)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request
        );
        $connection->handshake($request);
        $connection->onData('somedata');
        $this->assertTrue($connection->send('someotherdata'));
        return $connection;
    }

    /**
     * @dataProvider getValidHandshakeData
     * @expectedException Wrench\Exception\HandshakeException
     */
    public function testHandshakeBadSocket($path, $request)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getNotConnectedSocket(),
            $path,
            $request
        );
        $connection->handshake($request);
    }

    /**
     * Because expectation is that only $path application is available
     *
     * @dataProvider getWrongPathHandshakeData
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function testWrongPathHandshake($path, $request)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request
        );
        $connection->handshake($request);
    }

    /**
     * @dataProvider getValidHandleData
     */
    public function testHandle($path, $request_handshake, array $requests)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request_handshake
        );

        $connection->handshake($request_handshake);

        foreach ($requests as $request) {
            $connection->handle($request);
        }

        return $connection;
    }

    /**
     * @return Socket
     */
    protected function getConnectedSocket()
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
                ->method('isConnected')
                ->will($this->returnValue(true));

        return $socket;
    }

    /**
     * @return Socket
     */
    protected function getNotConnectedSocket()
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
                ->method('isConnected')
                ->will($this->returnValue(false));

        return $socket;
    }

    protected function getConnectionForHandshake($socket, $path, $request)
    {
        $manager = $this->getMockConnectionManager();

        $application = $this->getMockApplication();

        $server = $this->getMock('Wrench\Server', array(), array(), '', false);
        $server->registerApplication($path, $application);

        $manager->expects($this->any())
                ->method('getApplicationForPath')
                ->with($path)
                ->will($this->returnValue($application));

        $manager->expects($this->any())
                ->method('getServer')
                ->will($this->returnValue($server));

        $connection = $this->getInstance($manager, $socket);

        return $connection;
    }

    protected function getConnectionForHandle($socket, $path, $request)
    {
        $connection = $this->getConnectionForHandshake($socket, $path, $request);

        $valid = $this->getValidHandshakeData();


        return $connection;
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
        return $this->getMock('Wrench\Socket\ServerClientSocket', array(), array(), '', false);
    }

    /**
     * Gets a mock application
     *
     * @return EchoApplication
     */
    protected function getMockApplication()
    {
        return new EchoApplication();
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
                array('logger' => function () {},
                      'connection_id_algo' => 'sha512')
            )
        );
    }

    /**
     * Data provider
     */
    public function getValidHandleData()
    {
        $data = array();

        $valid_requests = array(
            array('foobar'),
            array('foo', 'bar')
        );

        $handshakes = $this->getValidHandshakeData();

        foreach ($handshakes as $handshake) {
            foreach ($valid_requests as $requests) {
                $arguments = $handshake;
                $arguments[] = $requests;
                $data[] = $arguments;
            }
        }

        return $data;
    }

    /**
     * Data provider
     */
    public function getValidHandshakeData()
    {
        return array(
            array(
                '/chat',
"GET /chat HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://example.com\r
Sec-WebSocket-Version: 13\r\n\r\n"
            )
        );
    }

    /**
     * Data provider
     */
    public function getWrongPathHandshakeData()
    {
        return array(
            array(
                '/foobar',
"GET /chat HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://example.com\r
Sec-WebSocket-Version: 13\r\n\r\n"
            ),
        );
    }
}
