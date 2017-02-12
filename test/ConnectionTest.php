<?php

namespace Wrench;

use Wrench\Application\DataHandlerInterface;
use Wrench\Application\EchoApplication;
use Wrench\Protocol\Protocol;
use Wrench\Socket\ServerClientSocket;
use Wrench\Socket\Socket;
use Wrench\Test\BaseTest;

/**
 * Tests the Connection class
 */
class ConnectionTest extends BaseTest
{
    /**
     * Tests the constructor
     *
     * @dataProvider getValidConstructorArguments
     */
    public function testConstructor($manager, $socket, array $options)
    {
        $this->assertInstanceOfClass(
            $instance = $this->getInstance(
                $manager,
                $socket,
                $options
            ),
            'Valid constructor arguments'
        );

        return $instance;
    }

    /**
     * @dataProvider getValidCloseCodes
     * @doesNotPerformAssertions
     */
    public function testClose($code)
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('getIp')
            ->will($this->returnValue('127.0.0.1'));

        $socket->expects($this->any())
            ->method('getPort')
            ->will($this->returnValue(random_int(1025, 50000)));

        $manager = $this->getMockConnectionManager();

        $connection = $this->getInstance($manager, $socket);
        $connection->close($code);
    }

    /**
     * Gets a mock socket
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Socket
     */
    protected function getMockSocket()
    {
        return $this->getMockBuilder(ServerClientSocket::class)
            ->setMethods(['getIp', 'getPort', 'isConnected', 'send'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConnectionManager
     */
    protected function getMockConnectionManager()
    {
        return $this->createMock(ConnectionManager::class);
    }

    /**
     * @dataProvider getValidHandshakeData
     */
    public function testHandshake($path, $request)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request
        );

        $connection->handshake($request);

        $headers = $connection->getHeaders();
        $this->assertEquals(array_change_key_case(['X-Some-Header' => 'Some Value']), $headers, 'Extra headers returned');

        $params = $connection->getQueryParams();
        $this->assertEquals(['someparam' => 'someval'], $params, 'Query string parameters returned');

        $connection->onData('somedata');
        $this->assertTrue($connection->send('someotherdata'));

        return $connection;
    }

    protected function getConnectionForHandshake($socket, $path, $request)
    {
        $manager = $this->getMockConnectionManager();

        $application = $this->getMockApplication();

        $server = $this->createMock(Server::class);
        $server->registerApplication($path, $application);

        $manager->expects($this->any())
            ->method('getApplicationForPath')
            ->with($path)
            ->will($this->returnValue($application));

        $manager->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue($server));

        $connection = $this->getInstance($manager, $socket);

        return $connection;
    }

    /**
     * Gets a mock application
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|EchoApplication
     */
    protected function getMockApplication()
    {
        return $this->createMock(DataHandlerInterface::class);
    }

    /**
     * @return Socket|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConnectedSocket()
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));

        $socket->expects($this->any())
            ->method('send')
            ->will($this->returnValue(100));

        return $socket;
    }

    /**
     * @dataProvider getValidHandshakeData
     * @expectedException \Wrench\Exception\HandshakeException
     */
    public function testHandshakeBadSocket($path, $request)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getNotConnectedSocket(),
            $path,
            $request
        );
        $connection->handshake($request);
    }

    /**
     * @return Socket
     */
    protected function getNotConnectedSocket()
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(false));

        return $socket;
    }

    /**
     * Because expectation is that only $path application is available
     *
     * @dataProvider getWrongPathHandshakeData
     * @expectedException \PHPUnit\Framework\ExpectationFailedException
     */
    public function testWrongPathHandshake($path, $request)
    {
        $connection = $this->getConnectionForHandshake(
            $this->getConnectedSocket(),
            $path,
            $request
        );
        $connection->handshake($request);
    }

    /**
     * @dataProvider getValidHandleData
     */
    public function testHandle($path, $request_handshake, array $requests, array $counts)
    {
        $connection = $this->getConnectionForHandle(
            $this->getConnectedSocket(),
            $path,
            $request_handshake,
            $counts
        );

        $connection->handshake($request_handshake);

        foreach ($requests as $request) {
            $connection->handle($request);
        }

        return $connection;
    }

    protected function getConnectionForHandle($socket, $path, $handshake, array $counts)
    {
        $connection = $this->getConnectionForHandshake($socket, $path, $handshake);

        $manager = $this->getMockConnectionManager();

        $application = $this->getMockApplication();

        $application->expects($this->exactly(isset($counts['onData']) ? $counts['onData'] : 0))
            ->method('onData')
            ->will($this->returnValue(true));

        /**
         * @var $server Server|\PHPUnit_Framework_MockObject_MockObject
         */
        $server = $this->createMock(Server::class);
        $server->registerApplication($path, $application);

        $manager->expects($this->any())
            ->method('getApplicationForPath')
            ->with($path)
            ->will($this->returnValue($application));

        $manager->expects($this->exactly(isset($counts['removeConnection']) ? $counts['removeConnection'] : 0))
            ->method('removeConnection');

        $manager->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue($server));

        $connection = $this->getInstance($manager, $socket);

        return $connection;
    }

    /**
     * Data provider
     *
     * @return array<array<int>>
     */
    public function getValidCloseCodes()
    {
        $arguments = [];
        foreach (Protocol::CLOSE_REASONS as $code => $reason) {
            $arguments[] = [$code];
        }
        return $arguments;
    }

    /**
     * Data provider
     *
     * @return array<array<mixed>>
     */
    public function getValidConstructorArguments()
    {
        $socket = $this->getMockSocket();

        $socket->expects($this->any())
            ->method('getIp')
            ->will($this->returnValue('127.0.0.1'));

        $socket->expects($this->any())
            ->method('getPort')
            ->will($this->returnValue(random_int(1025, 50000)));

        $manager = $this->getMockConnectionManager();

        return [
            [
                $manager,
                $socket,
                ['logger' => function () {
                }],
            ],
            [
                $manager,
                $socket,
                ['logger' => function () {
                },
                    'connection_id_algo' => 'sha512'],
            ],
        ];
    }

    /**
     * Data provider
     *
     * Uses this awkward valid request array so that splitting of payloads
     * across multiple calls to handle can be tested
     *
     * testHandle($path, $request_handshake, array $requests, array $counts)
     */
    public function getValidHandleData()
    {
        $valid_requests = [
            [
                'data' => [
                    "\x81\xad\x2e\xab\x82\xac\x6f\xfe\xd6\xe4\x14\x8b\xf9\x8c\x0c"
                    . "\xde\xf1\xc9\x5c\xc5\xe3\xc1\x4b\x89\xb8\x8c\x0c\xcd\xed\xc3"
                    . "\x0c\x87\xa2\x8e\x5e\xca\xf1\xdf\x59\xc4\xf0\xc8\x0c\x91\xa2"
                    . "\x8e\x4c\xca\xf0\x8e\x53\x81\xad\xd4\xfd\x81\xfe\x95\xa8\xd5"
                    . "\xb6\xee\xdd\xfa\xde\xf6\x88\xf2\x9b\xa6\x93\xe0\x93\xb1\xdf"
                    . "\xbb\xde\xf6\x9b\xee\x91\xf6\xd1\xa1\xdc\xa4\x9c\xf2\x8d\xa3"
                    . "\x92\xf3\x9a\xf6\xc7\xa1\xdc\xb6\x9c\xf3\xdc\xa9\x81\x80\x8e"
                    . "\x12\xcd\x8e\x81\x8c\xf6\x8a\xf0\xee\x9a\xeb\x83\x9a\xd6\xe7"
                    . "\x95\x9d\x85\xeb\x97\x8b" // Four text frames
                ],
                'counts' => [
                    'onData' => 4,
                ],
            ],
            [
                'data' => [
                    "\x88\x80\xdc\x8e\xa2\xc5" // Close frame
                ],
                'counts' => [
                    'removeConnection' => 1,
                ],
            ],
        ];

        $data = [];

        $handshakes = $this->getValidHandshakeData();

        foreach ($handshakes as $handshake) {
            foreach ($valid_requests as $handle_args) {
                $arguments = $handshake;
                $arguments[] = $handle_args['data'];
                $arguments[] = $handle_args['counts'];

                $data[] = $arguments;
            }
        }

        return $data;
    }

    /**
     * Data provider
     */
    public function getValidHandshakeData()
    {
        return [
            [
                '/chat',
                "GET /chat?someparam=someval HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
X-Some-Header: Some Value\r
Origin: http://example.com\r
Sec-WebSocket-Version: 13\r\n\r\n",
            ],
        ];
    }

    /**
     * Data provider
     */
    public function getWrongPathHandshakeData()
    {
        return [
            [
                '/foobar',
                "GET /chat HTTP/1.1\r
Host: server.example.com\r
Upgrade: websocket\r
Connection: Upgrade\r
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r
Origin: http://example.com\r
Sec-WebSocket-Version: 13\r\n\r\n",
            ],
        ];
    }
}
