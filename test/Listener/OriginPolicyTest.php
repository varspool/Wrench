<?php

namespace Wrench\Listener;

use Wrench\Connection;

class OriginPolicyTest extends ListenerBaseTest
{
    public function testConstructor()
    {
        $instance = $this->getInstance([]);
        $this->assertInstanceOfClass($instance, 'No constructor arguments');
        return $instance;
    }

    /**
     * @dataProvider getValidArguments
     * @param array  $allowed
     * @param string $domain
     */
    public function testValidAllowed($allowed, $domain)
    {
        $instance = $this->getInstance($allowed);
        $this->assertTrue($instance->isAllowed($domain));
    }

    /**
     * @dataProvider getValidArguments
     * @param array  $allowed
     * @param string $domain
     */
    public function testValidHandshake($allowed, $domain)
    {
        $instance = $this->getInstance($allowed);

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->never())
            ->method('close');

        $instance->onHandshakeRequest($connection, '/', $domain, 'abc', []);
    }

    /**
     * @dataProvider getInvalidArguments
     * @param array  $allowed
     * @param string $bad_domain
     */
    public function testInvalidAllowed($allowed, $bad_domain)
    {
        $instance = $this->getInstance($allowed);
        $this->assertFalse($instance->isAllowed($bad_domain));
    }

    /**
     * @dataProvider getInvalidArguments
     * @param array  $allowed
     * @param string $bad_domain
     */
    public function testInvalidHandshake($allowed, $bad_domain)
    {
        $instance = $this->getInstance($allowed);

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('close');

        $instance->onHandshakeRequest($connection, '/', $bad_domain, 'abc', []);
    }

    /**
     * Data provider
     */
    public function getValidArguments()
    {
        return [
            [['localhost'], 'http://localhost'],
            [['foobar.com'], 'https://foobar.com'],
            [['https://foobar.com'], 'https://foobar.com'],
        ];
    }

    /**
     * Data provider
     */
    public function getInvalidArguments()
    {
        return [
            [['localhost'], 'localdomain'],
            [['foobar.com'], 'foobar.org'],
            [['https://foobar.com'], 'http://foobar.com'],
            [['http://foobar.com'], 'foobar.com'],
        ];
    }
}
