<?php

namespace Wrench\Tests\Payload;

use Wrench\Protocol\Protocol;
use Wrench\Payload\Payload;
use Wrench\Tests\Test;
use \Exception;

/**
 * Payload test
 */
abstract class PayloadTest extends Test
{
    /**
     * Gets the class under test
     *
     * @return string
     */
    abstract protected function getClass();

    /**
     * A fresh instance of the class being tested
     *
     * @var Payload
     */
    protected $payload;

    /**
     * @see PHPUnit_Payloadwork_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        $this->payload = $this->getNewPayload();
    }

    protected function getNewPayload()
    {
        $class = $this->getClass();
        return new $class();
    }

    /**
     * @see PHPUnit_Payloadwork_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($this->payload);
    }

    /**
     * @param string $payload
     * @dataProvider getValidEncodePayloads
     */
    public function testBijection($type, $payload)
    {
        // Encode the payload
        $this->payload->encode($payload, $type);

        // Create a new payload and read the data in with encode
        $payload = $this->getNewPayload();
        $payload->encode($this->payload->getPayload(), $type);

        // These still match
        $this->assertEquals(
            $this->payload->getType(),
            $payload->getType(),
            'Types match after encode -> receiveData'
        );

        $this->assertEquals(
            $this->payload->getPayload(),
            $payload->getPayload(),
            'Payloads match after encode -> receiveData'
        );
    }

    /**
     * @param string $payload
     * @dataProvider getValidEncodePayloads
     */
    public function testEncodeTypeReflection($type, $payload)
    {
        $this->payload->encode($payload, Protocol::TYPE_TEXT);
        $this->assertEquals(Protocol::TYPE_TEXT, $this->payload->getType(), 'Encode retains type information');
    }

    /**
     * @param string $payload
     * @dataProvider getValidEncodePayloads
     */
    public function testEncodePayloadReflection($type, $payload)
    {
        $this->payload->encode($payload, Protocol::TYPE_TEXT);
        $this->assertEquals($payload, $this->payload->getPayload(), 'Encode retains payload information');
    }

    /**
     * Data provider
     *
     * @return array<string>
     */
    public function getValidEncodePayloads()
    {
        return array(
            array(
                Protocol::TYPE_TEXT,
                "123456\x007890!@#$%^&*()qwe\trtyuiopQWERTYUIOPasdfghjklASFGH\n
                JKLzxcvbnmZXCVBNM,./<>?;[]{}-=_+\|'asdad0x11\aasdassasdasasdsd"
            ),
            array(
                Protocol::TYPE_TEXT,
                pack('CCCCCCC', 0x00, 0x01, 0x02, 0x03, 0x04, 0xff, 0xf0)
            ),
            array(Protocol::TYPE_TEXT, ' ')
        );
    }
}