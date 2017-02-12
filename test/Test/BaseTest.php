<?php

namespace Wrench\Test;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Wrench\ConnectionManager;

/**
 * Test base class
 */
abstract class BaseTest extends TestCase
{
    /**
     * Asserts that the given instance is of the class under test
     *
     * @param object $instance
     * @param string $message Optional
     */
    public function assertInstanceOfClass($instance, $message = null)
    {
        $this->assertInstanceOf(
            $this->getClass(),
            $instance,
            $message
        );
    }

    /**
     * Gets the class under test
     *
     * @return string
     */
    protected function getClass()
    {
        $class = static::class;

        if (preg_match('/(.*)Test$/', $class, $matches)) {
            return $matches[1];
        }

        throw new \LogicException(
            'Cannot automatically determine class under test; configure manually by overriding getClass()'
        );
    }

    /**
     * Gets an instance of the class under test
     *
     * @magic This method accepts a variable number of arguments
     * @param array $args
     * @return static|object of type $this->getClass()
     */
    public function getInstance(...$args)
    {
        $reflection = new ReflectionClass($this->getClass());
        return $reflection->newInstanceArgs($args);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConnectionManager
     */
    protected function getMockConnectionManager()
    {
        return $this->createMock(ConnectionManager::class);
    }
}
