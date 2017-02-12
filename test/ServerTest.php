<?php

namespace Wrench;

use Wrench\Test\BaseTest;
use Wrench\Util\LoopInterface;

/**
 * Tests the Server class
 */
class ServerTest extends BaseTest
{
    /**
     * Tests the constructor
     *
     * @param string $url
     * @param array  $options
     * @dataProvider getValidConstructorArguments
     */
    public function testConstructor($url, array $options = [])
    {
        $this->assertInstanceOfClass(
            $this->getInstance($url, $options),
            'Valid constructor arguments'
        );
    }

    /**
     * Data provider
     *
     * @return array<array<mixed>>
     */
    public function getValidConstructorArguments()
    {
        return [
            [
                'ws://localhost:8000',
                [],
            ],
            [
                'ws://localhost',
            ],
        ];
    }

    public function testLoop()
    {
        /**
         * A simple loop that only runs 5 times
         */
        $countLoop = new class implements LoopInterface
        {
            public $count = 0;

            public function shouldContinue(): bool
            {
                return ($this->count++ < 5);
            }
        };

        $c = $this->getMockConnectionManager();

        $c->expects($this->exactly(5))
            ->method('selectAndProcess');

        $server = new Server('ws://localhost:8000', [
            'connection_manager' => $c,
        ]);
        $server->setLoop($countLoop);
        $server->run();
    }
}
