<?php

namespace Wrench;

use Wrench\Test\BaseTest;

/**
 * Tests the Server class
 */
class ServerTest extends BaseTest
{
    /**
     * Tests the constructor
     *
     * @param string $url
     * @param array $options
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
                'ws://localhost'
            ],
        ];
    }
}
