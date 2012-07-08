<?php

namespace Wrench\Tests\Protocol;

use Wrench\Tests\Test;
use \Exception;

abstract class ProtocolTest extends Test
{
    abstract protected function getClass();

    protected $protocol;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $class = $this->getClass();
        $this->protocol = new $class();
    }

    /**
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset($this->protocol);
    }

    /**
     * @dataProvider getValidHandshakeResponses
     */
    public function testValidateHandshakeResponseValid($response, $key)
    {
        try {
            $response = $this->protocol->validateResponseHandshake($response, $key);
        } catch (Exception $e) {
            $this->fail('Validated valid response handshake as invalid');
        }
    }

    /**
     * @dataProvider getValidOriginUris
     */
    public function testValidateOriginUriValid($uri)
    {
        try {
            $this->protocol->validateOriginUri($uri);
        } catch (\Exception $e) {
            $this->fail('Valid URI validated as invalid');
        }
    }

    /**
     * @dataProvider getInvalidOriginUris
     * @expectedException InvalidArgumentException
     */
    public function testValidateOriginUriInvalid($uri)
    {
        $this->protocol->validateOriginUri($uri);
    }

    public function getValidOriginUris()
    {
        return array(
            array('http://www.example.org'),
            array('http://www.example.com/some/page'),
            array('https://localhost/')
        );
    }

    public function getInvalidOriginUris()
    {
        return array(
            array(false),
            array(true),
            array(''),
            array('blah')
        );
    }

    public function getValidHandshakeResponses()
    {
       $cases = array();

       for ($i = 10; $i > 0; $i--) {
           $key = sha1(time() . uniqid('', true));
           $response = "Sec-WebSocket-Accept: "
               . base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true))
               . "\r\n\r\n";

           $cases[] = array($response, $key);
       }

       return $cases;
    }
}