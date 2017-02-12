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
     * Tests logging
     */
    public function testLogging()
    {
        $test = $this;
        $logged = false;

        $server = $this->getInstance('ws://localhost:8000', [
            'logger' => function ($message, $priority) use ($test, &$logged) {
                $test->assertTrue(is_string($message), 'Log had a string message');
                $test->assertTrue(is_string($priority), 'Log had a string priority');
                $logged = true;
            },
        ]);

        $this->assertTrue($logged, 'The log callback was hit');
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
                ['logger' => [$this, 'log']],
            ],
            [
                'ws://localhost',
                ['logger' => [$this, 'log']],
            ],
        ];
    }

    protected function getClass()
    {
        return 'Wrench\Server';
    }
}
