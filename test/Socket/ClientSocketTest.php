<?php

namespace Wrench\Socket;

use InvalidArgumentException;
use stdClass;
use Wrench\Protocol\Rfc6455Protocol;
use Wrench\Test\ServerTestHelper;

class ClientSocketTest extends UriSocketTest
{
    /**
     * Overridden to use with the depends annotation
     */
    public function testConstructor()
    {
        $instance = parent::testConstructor();

        $socket = null;

        $this->assertInstanceOfClass(
            new ClientSocket('ws://localhost/'),
            'ws:// scheme, default port'
        );

        $this->assertInstanceOfClass(
            new ClientSocket('ws://localhost/some-arbitrary-path'),
            'with path'
        );

        $this->assertInstanceOfClass(
            new ClientSocket('wss://localhost/test', []),
            'empty options'
        );

        $this->assertInstanceOfClass(
            new ClientSocket('ws://localhost:8000/foo'),
            'specified port'
        );

        return $instance;
    }

    public function testOptions()
    {
        $socket = null;

        $this->assertInstanceOfClass(
            $socket = new ClientSocket(
                'ws://localhost:8000/foo',
                [
                    'timeout_connect' => 10,
                ]
            ),
            'connect timeout'
        );

        $this->assertInstanceOfClass(
            $socket = new ClientSocket(
                'ws://localhost:8000/foo',
                [
                    'timeout_socket' => 10,
                ]
            ),
            'socket timeout'
        );

        $this->assertInstanceOfClass(
            $socket = new ClientSocket(
                'ws://localhost:8000/foo',
                [
                    'protocol' => new Rfc6455Protocol(),
                ]
            ),
            'protocol'
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testProtocolTypeError()
    {
        $socket = new ClientSocket(
            'ws://localhost:8000/foo',
            [
                'protocol' => new stdClass(),
            ]
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriEmpty()
    {
        $w = new ClientSocket(null);
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriInvalid()
    {
        $w = new ClientSocket('Bad argument');
    }


    /**
     * @depends testConstructor
     * @expectedException \Wrench\Exception\SocketException
     */
    public function testSendTooEarly($instance)
    {
        $instance->send('foo');
    }

    /**
     * Test the connect, send, receive method
     *
     * @medium
     */
    public function testConnect()
    {
        try {
            $helper = new ServerTestHelper();
            $helper->setUp();

            $instance = $this->getInstance($helper->getConnectionString());
            $success = $instance->connect();

            $this->assertTrue($success, 'Client socket can connect to test server');

            $sent = $instance->send("GET /echo HTTP/1.1\r
Host: localhost\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://localhost\r
Sec-WebSocket-Version: 13\r\n\r\n");
            $this->assertNotFalse($sent, 'Client socket can send to test server');

            $response = $instance->receive();
            $this->assertStringStartsWith('HTTP', $response, 'Response looks like HTTP handshake response');
        } catch (\Exception $e) {
            $helper->tearDown();
            throw $e;
        }

        $helper->tearDown();
    }
}
