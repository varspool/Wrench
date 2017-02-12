<?php

namespace Wrench;

use Countable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wrench\Application\ConnectionHandlerInterface;
use Wrench\Application\DataHandlerInterface;
use Wrench\Application\UpdateHandlerInterface;
use Wrench\Exception\CloseException;
use Wrench\Exception\Exception as WrenchException;
use Wrench\Socket\ServerClientSocket;
use Wrench\Socket\ServerSocket;
use Wrench\Util\Configurable;

class ConnectionManager extends Configurable implements Countable, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TIMEOUT_SELECT = 0;
    const TIMEOUT_SELECT_MICROSEC = 200000;

    /**
     * @var Server
     */
    protected $server;

    /**
     * Master socket
     *
     * @var ServerSocket
     */
    protected $socket;

    /**
     * An array of client connections
     *
     * @var array<int => Connection>
     */
    protected $connections = [];

    /**
     * An array of raw socket resources, corresponding to connections, roughly
     *
     * @var array<int => resource>
     */
    protected $resources = [];

    /**
     * Constructor
     *
     * @param Server $server
     * @param array  $options
     */
    public function __construct(Server $server, array $options = [])
    {
        $this->logger = new NullLogger();
        $this->server = $server;

        parent::__construct($options);
    }

    /**
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->connections);
    }

    /**
     * Gets the application associated with the given path
     *
     * @param string $path
     * @return DataHandlerInterface|ConnectionHandlerInterface|UpdateHandlerInterface
     */
    public function getApplicationForPath($path)
    {
        $path = ltrim($path, '/');
        return $this->server->getApplication($path);
    }

    /**
     * Listens on the main socket
     *
     * @return void
     */
    public function listen(): void
    {
        $this->socket->listen();
        $this->resources[$this->socket->getResourceId()] = $this->socket->getResource();
    }

    /**
     * Select and process an array of resources
     */
    public function selectAndProcess(): void
    {
        $read = $this->resources;
        $unused_write = null;
        $unsued_exception = null;

        stream_select(
            $read,
            $unused_write,
            $unused_exception,
            $this->options['timeout_select'],
            $this->options['timeout_select_microsec']
        );

        foreach ($read as $socket) {
            if ($socket == $this->socket->getResource()) {
                $this->processMasterSocket();
            } else {
                $this->processClientSocket($socket);
            }
        }
    }

    /**
     * Process events on the master socket ($this->socket)
     *
     * @return void
     */
    protected function processMasterSocket(): void
    {
        $new = null;

        try {
            $new = $this->socket->accept();
        } catch (Exception $e) {
            $this->logger->error('Socket error: {exception}', [
                'exception' => $e,
            ]);
            return;
        }

        $connection = $this->createConnection($new);
        $this->server->notify(Server::EVENT_SOCKET_CONNECT, [$new, $connection]);
    }

    /**
     * Creates a connection from a socket resource
     * The create connection object is based on the options passed into the
     * constructor ('connection_class', 'connection_options'). This connection
     * instance and its associated socket resource are then stored in the
     * manager.
     *
     * @param resource $resource A socket resource
     * @return Connection|LoggerAwareInterface
     */
    protected function createConnection($resource): Connection
    {
        if (!$resource || !is_resource($resource)) {
            throw new InvalidArgumentException('Invalid connection resource');
        }

        $socket_class = $this->options['socket_client_class'];
        $socket_options = $this->options['socket_client_options'];

        $connection_class = $this->options['connection_class'];
        $connection_options = $this->options['connection_options'];

        $socket = new $socket_class($resource, $socket_options);
        $connection = new $connection_class($this, $socket, $connection_options);

        if ($connection instanceof LoggerAwareInterface) {
            $connection->setLogger($this->logger);
        }

        $id = $this->resourceId($resource);
        $this->resources[$id] = $resource;
        $this->connections[$id] = $connection;

        return $connection;
    }

    /**
     * This server makes an explicit assumption: PHP resource types may be cast
     * to a integer. Furthermore, we assume this is bijective. Both seem to be
     * true in most circumstances, but may not be guaranteed.
     * This method (and $this->getResourceId()) exist to make this assumption
     * explicit.
     * This is needed on the connection manager as well as on resources
     *
     * @param resource $resource
     * @return int
     */
    protected function resourceId($resource): int
    {
        return (int)$resource;
    }

    /**
     * Process events on a client socket
     *
     * @param resource $socket
     */
    protected function processClientSocket($socket): void
    {
        $connection = $this->getConnectionForClientSocket($socket);

        if (!$connection) {
            $this->logger->warning('No connection for client socket');
            return;
        }

        try {
            $this->server->notify(Server::EVENT_CLIENT_DATA, [$socket, $connection]);

            $connection->process();
        } catch (CloseException $e) {
            $this->logger->notice('Client connection closed: {exception}', [
                'exception' => $e,
            ]);
            $connection->close($e);
        } catch (WrenchException $e) {
            $this->logger->warning('Error on client socket: {exception}', [
                'exception' => $e,
            ]);
            $connection->close($e);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Wrong input arguments: {exception}', [
                'exception' => $e,
            ]);
            $connection->close($e);
        }
    }

    /**
     * Returns the Connection associated with the specified socket resource
     *
     * @param resource $socket
     * @return Connection
     */
    protected function getConnectionForClientSocket($socket)
    {
        if (!isset($this->connections[$this->resourceId($socket)])) {
            return false;
        }
        return $this->connections[$this->resourceId($socket)];
    }

    /**
     * Gets the connection manager's listening URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->server->getUri();
    }

    /**
     * @return \Wrench\Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Removes a connection
     *
     * @param Connection $connection
     */
    public function removeConnection(Connection $connection)
    {
        $socket = $connection->getSocket();

        if ($socket->getResource()) {
            $index = $socket->getResourceId();
        } else {
            $index = array_search($connection, $this->connections);
        }

        if (!$index) {
            $this->logger->warning('Could not remove connection: not found', 'warning');
        }

        unset($this->connections[$index]);
        unset($this->resources[$index]);

        $this->server->notify(
            Server::EVENT_SOCKET_DISCONNECT,
            [$connection->getSocket(), $connection]
        );
    }

    /**
     * @see Socket::configure()
     *   Options include:
     *     - timeout_select          => int, seconds, default 0
     *     - timeout_select_microsec => int, microseconds (NB: not milli), default: 200000
     */
    protected function configure(array $options)
    {
        $options = array_merge([
            'socket_master_class' => ServerSocket::class,
            'socket_master_options' => [],
            'socket_client_class' => ServerClientSocket::class,
            'socket_client_options' => [],
            'connection_class' => Connection::class,
            'connection_options' => [],
            'timeout_select' => self::TIMEOUT_SELECT,
            'timeout_select_microsec' => self::TIMEOUT_SELECT_MICROSEC,
        ], $options);

        parent::configure($options);

        $this->configureMasterSocket();
    }

    /**
     * Configures the main server socket
     */
    protected function configureMasterSocket()
    {
        $class = $this->options['socket_master_class'];
        $options = $this->options['socket_master_options'];
        $this->socket = new $class($this->server->getUri(), $options);
    }

    /**
     * Gets all resources
     *
     * @return array<int => resource)
     */
    protected function getAllResources()
    {
        return array_merge($this->resources, [
            $this->socket->getResourceId() => $this->socket->getResource(),
        ]);
    }
}
