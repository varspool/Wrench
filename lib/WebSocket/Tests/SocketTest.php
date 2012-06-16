<?php

namespace WebSocket\Tests;

use WebSocket\Protocol\Rfc6455Protocol;

use WebSocket\Socket;

use \stdClass;
use \InvalidArgumentException;
use \PHPUnit_Framework_Error;

class SocketTest extends Test
{
    public function testConstructor()
    {
        $socket = null;

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket('ws://localhost/'),
            'ws:// scheme, default port'
        );

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket('ws://localhost/some-arbitrary-path'),
            'with path'
        );

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket('wss://localhost/test', array()),
            'empty options'
        );

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket('ws://localhost:8000/foo'),
            'specified port'
        );
    }

    public function testOptions()
    {
        $socket = null;

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket(
                'ws://localhost:8000/foo', array(
                    'timeout_connect' => 10
                )
            ),
            'connect timeout'
        );

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket(
                'ws://localhost:8000/foo', array(
                    'timeout_socket' => 10
                )
            ),
            'socket timeout'
        );

        $this->assertInstanceOf(
            'WebSocket\Socket',
            $socket = new Socket(
                'ws://localhost:8000/foo', array(
                    'protocol' => new Rfc6455Protocol()
                )
            ),
            'protocol'
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testProtocolTypeError()
    {
        $socket = new Socket(
            'ws://localhost:8000/foo', array(
                'protocol' => new stdClass()
            )
        );
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorUriUnspecified()
    {
        $w = new Socket();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriEmpty()
    {
        $w = new Socket(null);
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriInvalid()
    {
        $w = new Socket('Bad argument');
    }

}
