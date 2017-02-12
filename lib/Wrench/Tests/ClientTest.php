<?php

namespace Wrench\Tests;

use InvalidArgumentException;
use Wrench\Client;
use Wrench\Protocol\Protocol;

/**
 * Tests the client class
 */
class ClientTest extends Test
{
    public function testConstructor()
    {
        $this->assertInstanceOfClass(
            $client = new Client(
                'ws://localhost/test',
                'http://example.org/'
            ),
            'ws:// scheme, default socket'
        );

        $this->assertInstanceOfClass(
            $client = new Client(
                'ws://localhost/test',
                'http://example.org/',
                array('socket' => $this->getMockSocket())
            ),
            'ws:// scheme, socket specified'
        );
    }

    /**
     * Gets a mock socket
     *
     * @return \Wrench\Socket\Socket|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockSocket()
    {
        return $this->getMockBuilder('Wrench\Socket\ClientSocket')
            ->setMethods(array())
            ->setConstructorArgs(array('wss://localhost:8000'))
            ->getMock();
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

    public function testSend()
    {
        try {
            $helper = new ServerTestHelper();
            $helper->setUp();

            /* @var $instance \Wrench\Client */
            $instance = $this->getInstance($helper->getEchoConnectionString(), 'http://www.example.com/send');
            $instance->addRequestHeader('X-Test', 'Custom Request Header');

            $this->assertFalse($instance->receive(), 'Receive before connect');

            $success = $instance->connect();
            $this->assertTrue($success, 'Client can connect to test server');
            $this->assertTrue($instance->isConnected());

            try {
                $instance->sendData('blah', 9999);
            } catch (\Exception $ex) {
                $this->assertInstanceOf('InvalidArgumentException', $ex, 'Test sending invalid type');
            }

            try {
                $instance->sendData('blah', 'fooey');
            } catch (\Exception $ex) {
                $this->assertInstanceOf('InvalidArgumentException', $ex, 'Test sending invalid type string');
            }

            $this->assertFalse($instance->connect(), 'Double connect');

            $this->assertFalse((boolean)$instance->receive(), 'No data');

            $bytes = $instance->sendData('foobar', 'text');
            $this->assertTrue($bytes >= 6, 'sent text frame');
            sleep(1);

            $bytes = $instance->sendData('baz', Protocol::TYPE_TEXT);
            $this->assertTrue($bytes >= 3, 'sent text frame');
            sleep(1);

            $responses = $instance->receive();
            $this->assertTrue(is_array($responses));
            $this->assertCount(2, $responses);
            $this->assertInstanceOf('Wrench\\Payload\\Payload', $responses[0]);
            $this->assertInstanceOf('Wrench\\Payload\\Payload', $responses[1]);

            $bytes = $instance->sendData('baz', Protocol::TYPE_TEXT);
            $this->assertTrue($bytes >= 3, 'sent text frame');
            sleep(1);

            # test fix for issue #43
            $responses = $instance->receive();
            $this->assertTrue(is_array($responses));
            $this->assertCount(1, $responses);
            $this->assertInstanceOf('Wrench\\Payload\\Payload', $responses[2]);

            $instance->disconnect();

            $this->assertFalse($instance->isConnected());
        } catch (\Exception $e) {
            $helper->tearDown();
            throw $e;
        }

        $helper->tearDown();
    }

    /**
     * @see \Wrench\Tests.Test::getClass()
     */
    protected function getClass()
    {
        return 'Wrench\Client';
    }
}
