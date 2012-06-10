<?php

namespace WebSocket\Tests;

use Varspool\WebsocketBundle\Server\Server;

use WebSocket\ConnectionException;
use WebSocket\Tests\Test;

abstract class ListenTest extends Test
{
    const LISTEN_PORT = 8000;
    const LISTEN_INTERFACE = 'localhost';

    protected $ssl    = false;
    protected $server_process;

    /**
     * @return string
     */
    protected function getServerCommand()
    {
        return escapeshellcmd('php')
            . ' ' . escapeshellarg(dirname(__FILE__) . '/server.php')
            . ' ' . escapeshellarg($this->getUri());
    }

    /**
     * @return string
     */
    protected function getUri()
    {
        return 'ws://localhost:12493';
    }

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->server_process = popen($this->getServerCommand(), 'r');

        sleep(1000);

        if (!$this->server_process) {
            throw new ConnectionException('Could not start server needed for test', self::LISTEN_PORT);
        }
    }

    /**
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();

        if ($this->server_process) {
            pclose($this->server_process);
        }

        $this->server_process = null;
    }
}