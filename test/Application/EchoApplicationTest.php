<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Protocol\Protocol;
use Wrench\Test\BaseTest as WrenchTest;

class EchoApplicationTest extends WrenchTest
{
    /**
     * Tests the constructor
     */
    public function testConstructor()
    {
        $this->assertInstanceOfClass($this->getInstance());
    }

    /**
     * @dataProvider getValidPayloads
     * @small
     */
    public function testOnData($payload)
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('send')
            ->with($this->equalTo($payload), $this->equalTo(Protocol::TYPE_TEXT))
            ->will($this->returnValue(true));

        $this->getInstance()->onData($payload, $connection);
    }

    /**
     * Data provider
     *
     * @return array<array<string>>
     */
    public function getValidPayloads()
    {
        return [
            ['asdkllakdaowidoaw noaoinosdna nwodinado ndsnd aklndiownd'],
            [' '],
        ];
    }
}
