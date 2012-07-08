<?php

namespace Wrench\Tests;

use Wrench\Client;
use Wrench\Tests\Test;
use Wrench\Socket;

use \InvalidArgumentException;
use \PHPUnit_Framework_Error;

/**
 * Tests the client class
 */
class ClientTest extends Test
{
    /**
     * @see Wrench\Tests.Test::getClass()
     */
    protected function getClass()
    {
        return 'Wrench\Client';
    }

    public function testConstructor()
    {
        $this->assertInstanceOfClass(
            $client = new Client(
                'ws://localhost/test', 'http://example.org/'
            ),
            'ws:// scheme, default socket'
        );

        $this->assertInstanceOfClass(
            $client = new Client(
                'ws://localhost/test', 'http://example.org/',
                array('socket' => $this->getMockSocket())
            ),
            'ws:// scheme, socket specified'
        );
    }

    /**
     * Gets a mock socket
     */
    protected function getMockSocket()
    {
        return $this->getMock('Wrench\Socket\ClientSocket', array(), array('wss://localhost:8000'));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorSocketUnspecified()
    {
        $w = new Client();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriInvalid()
    {
        $w = new Client('invalid uri', 'http://www.example.com/');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriEmpty()
    {
        $w = new Client(null, 'http://www.example.com/');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriPathUnspecified()
    {
        $w = new Client('ws://localhost', 'http://www.example.com/');
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorOriginUnspecified()
    {
        $w = new Client('ws://localhost');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorOriginEmpty()
    {
        $w = new Client('wss://localhost', null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorOriginInvalid()
    {
        $w = new Client('ws://localhost:8000', 'NOTAVALIDURI');
    }
}
