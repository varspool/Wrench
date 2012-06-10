<?php

namespace WebSocket\Tests;

use WebSocket\Client;
use WebSocket\Tests\Test;
use WebSocket\Socket;

use \InvalidArgumentException;
use \PHPUnit_Framework_Error;

class ClientTest extends ListenTest
{
    protected function getUri()
    {
        return parent::getUri() . '/test';
    }

    public function testConstructor()
    {
        $client = null;

        $this->assertInstanceOf(
            'WebSocket\Client',
            $client = new Client(
                'ws://localhost/test', 'http://example.org/'
            ),
            'ws:// scheme, default socket'
        );

        $this->assertInstanceOf(
            'WebSocket\Client',
            $client = new Client(
                'ws://localhost/test', 'http://example.org/',
                array('socket' => $this->getMockSocket())
            ),
            'ws:// scheme, socket specified'
        );

        $this->assertInstanceOf(
            'WebSocket\Client',
            $client = new Client(
                $this->getUri(), 'http://example.org/'
            ),
            'listening uri'
        );

        return $client;
    }

    /**
     * Gets a mock socket
     */
    protected function getMockSocket()
    {
        return $this->getMock('WebSocket\Socket', array(), array($this->getUri()));
    }

    protected function getMockProtocol()
    {
        return $this->getMock('WebSocket\Protocol\Rfc6455Protocol');
    }

    /**
     * @depends testConstructor
     */
    public function testConnect(Client $client)
    {
        $this->assertTrue($client->connect(), 'Could not connect client');
        return $client;
    }

    /**
     * @depends testConnect
     */
    public function testSendData(Client $client)
    {
        $response = $client->sendData('Some message');
        $this->assertTrue($response);
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
        $w = new Client($this->getUri());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorOriginEmpty()
    {
        $w = new Client($this->getUri(), null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorOriginInvalid()
    {
        $w = new Client($this->getUri(), 'NOTAVALIDURI');
    }


}
