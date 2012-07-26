<?php

namespace Wrench\Tests\Socket;

use Wrench\Protocol\Rfc6455Protocol;
use Wrench\Socket\ClientSocket;
use \Exception;
use \stdClass;

class ClientSocketTest extends UriSocketTest
{
    const TEST_SERVER_PORT_MIN = 16666;
    const TEST_SERVER_PORT_MAX = 52222;

    public static $nextPort = null;

    protected $port = null;
    protected $process = null;
    protected $pipes = array();

    /**
     * Gets the next available port number to start a server on
     */
    public static function getNextPort()
    {
        if (self::$nextPort === null) {
            self::$nextPort = mt_rand(self::TEST_SERVER_PORT_MIN, self::TEST_SERVER_PORT_MAX);
        }
        return self::$nextPort++;
    }

    /**
     * @see Wrench\Tests.Test::getClass()
     */
    public function getClass()
    {
        return 'Wrench\Socket\ClientSocket';
    }

    /**
     * Gets the server command
     *
     * @return string
     */
    protected function getCommand()
    {
        return sprintf('/usr/bin/env php %s/../server.php %d', __DIR__, $this->port);
    }

    /**
     * Starts a listening server
     */
    protected function startProcess()
    {
        $this->port = self::getNextPort();

        $this->process = proc_open(
            $this->getCommand(),
            array(
                0 => array('file', '/dev/null', 'r'),
                1 => array('file', __DIR__ . '/../../../../build/server.log', 'a+'),
                2 => array('file', __DIR__ . '/../../../../build/server.err.log', 'a+')
            ),
            $this->pipes,
            __DIR__ . '../'
        );

        sleep(3);
    }

    /**
     * Stops the listening server
     */
    protected function stopProcess()
    {
        if ($this->process) {
            foreach ($this->pipes as &$pipe) {
                fclose($pipe);
            }
            $this->pipes = null;

            // Sigh
            $status = proc_get_status($this->process);

            if ($status && isset($status['pid']) && $status['pid']) {
                // More sigh, this is the pid of the parent sh process, we want
                //  to terminate the server directly
                $this->log('Command: /bin/ps -ao pid,ppid | /usr/bin/col | /usr/bin/tail -n +2 | /bin/grep \'  ' . $status['pid'] . "'", 'info');
                exec('/bin/ps -ao pid,ppid | /usr/bin/col | /usr/bin/tail -n +2 | /bin/grep \' ' . $status['pid'] . "'", $processes, $return);

                if ($return === 0) {
                    foreach ($processes as $process) {
                        list($pid, $ppid) = explode(' ', str_replace('  ', ' ', $process));
                        if ($pid) {
                            $this->log('Killing ' . $pid, 'info');
                            exec('/bin/kill ' . $pid . ' > /dev/null 2>&1');
                        }
                    }
                } else {
                    $this->log('Unable to find child processes', 'warning');
                }

                sleep(1);

                $this->log('Killing ' . $status['pid'], 'info');
                exec('/bin/kill ' . $status['pid'] . ' > /dev/null 2>&1');

                sleep(1);
            }

            proc_close($this->process);
            unset($this->process);
        }
    }

    public function log($message, $priority = 'info')
    {
        //echo $message . "\n";
    }

    /**
     * Overriden to use with the depends annotation
     *
     * @see Wrench\Tests\Socket.UriSocketTest::testConstructor()
     */
    public function testConstructor()
    {
        $instance = parent::testConstructor();

        $socket = null;

        $this->assertInstanceOfClass(
            new ClientSocket('ws://localhost/'),
            'ws:// scheme, default port'
        );

        $this->assertInstanceOfClass(
            new ClientSocket('ws://localhost/some-arbitrary-path'),
            'with path'
        );

        $this->assertInstanceOfClass(
            new ClientSocket('wss://localhost/test', array()),
            'empty options'
        );

        $this->assertInstanceOfClass(
            new ClientSocket('ws://localhost:8000/foo'),
            'specified port'
        );

        return $instance;
    }

    public function testOptions()
    {
        $socket = null;

        $this->assertInstanceOfClass(
            $socket = new ClientSocket(
                'ws://localhost:8000/foo', array(
                    'timeout_connect' => 10
                )
            ),
            'connect timeout'
        );

        $this->assertInstanceOfClass(
            $socket = new ClientSocket(
                'ws://localhost:8000/foo', array(
                    'timeout_socket' => 10
                )
            ),
            'socket timeout'
        );

        $this->assertInstanceOfClass(
            $socket = new ClientSocket(
                'ws://localhost:8000/foo', array(
                    'protocol' => new Rfc6455Protocol()
                )
            ),
            'protocol'
        );
    }

      /**
     * @expectedException InvalidArgumentException
     */
    public function testProtocolTypeError()
    {
        $socket = new ClientSocket(
            'ws://localhost:8000/foo', array(
                'protocol' => new stdClass()
            )
        );
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorUriUnspecified()
    {
        $w = new ClientSocket();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriEmpty()
    {
        $w = new ClientSocket(null);
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorUriInvalid()
    {
        $w = new ClientSocket('Bad argument');
    }


    /**
     * @depends testConstructor
     * @expectedException Wrench\Exception\SocketException
     */
    public function testSendTooEarly($instance)
    {
        $instance->send('foo');
    }

    /**
     * Test the connect, send, receive method
     */
    public function testConnect()
    {
        $this->startProcess();

        // Wait for server to come up
        $instance = $this->getInstance('ws://localhost:' . $this->port);
        $success = $instance->connect();

        $this->assertTrue($success, 'Client socket can connect to test server');

        $sent = $instance->send("GET /echo HTTP/1.1\r
Host: localhost\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://localhost\r
Sec-WebSocket-Version: 13\r\n\r\n");
        $this->assertNotEquals(false, $sent, 'Client socket can send to test server');

        $response = $instance->receive();
        $this->assertStringStartsWith('HTTP', $response, 'Response looks like HTTP handshake response');

        $this->stopProcess();
    }
}