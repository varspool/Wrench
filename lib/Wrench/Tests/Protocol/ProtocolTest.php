<?php

namespace Wrench\Tests\Protocol;

use Wrench\Tests\Test;
use \Exception;

abstract class ProtocolTest extends Test
{
    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider getValidHandshakeResponses
     */
    public function testValidateHandshakeResponseValid($response, $key)
    {
        try {
            $valid = $this->getInstance()->validateResponseHandshake($response, $key);
            $this->assertTrue(is_bool($valid), 'Validation return value is boolean');
            $this->assertTrue($valid, 'Handshake response validates');
        } catch (Exception $e) {
            $this->fail('Validated valid response handshake as invalid');
        }
    }

    /**
     * @dataProvider getValidHandshakeResponses
     */
    public function testGetResponseHandsake($unused, $key)
    {
        try {
            $response = $this->getInstance()->getResponseHandshake($key);
            $this->assertHttpResponse($response);
        } catch (Exception $e) {
            $this->fail('Unable to get handshake response: ' . $e);
        }
    }

    /**
     * Asserts the string response is an HTTP response
     *
     * @param string $response
     */
    protected function assertHttpResponse($response, $message = '')
    {
        $this->assertStringStartsWith('HTTP', $response, $message . ' - response starts well');
        $this->assertStringEndsWith("\r\n", $response, $message . ' - response ends well');
    }

    public function testGetResponseError()
    {
        $response = $this->getInstance()->getResponseError(400);
        $this->assertHttpResponse($response, 'Code as int');

        $response = $this->getInstance()->getResponseError(new Exception('Some message', 500));
        $this->assertHttpResponse($response, 'Code in Exception');

        $response = $this->getInstance()->getResponseError(888);
        $this->assertHttpResponse($response, 'Invalid code produces unimplemented response');
    }

    /**
     * @dataProvider getValidOriginUris
     */
    public function testValidateOriginUriValid($uri)
    {
        try {
            $this->getInstance()->validateOriginUri($uri);
        } catch (\Exception $e) {
            $this->fail('Valid URI validated as invalid: ' . $e);
        }
    }

    /**
     * @dataProvider getInvalidOriginUris
     * @expectedException InvalidArgumentException
     */
    public function testValidateOriginUriInvalid($uri)
    {
        $this->getInstance()->validateOriginUri($uri);
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