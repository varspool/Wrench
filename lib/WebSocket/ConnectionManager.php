<?php
namespace WebSocket;

use WebSocket\Resource;
use WebSocket\Util\Configurable;

class ConnectionManager extends Configurable
{
    const TIMEOUT_SELECT = 5;

    /**
     * @var Server
     */
    protected $server;

    /**
     * Master socket
     *
     * @var Socket
     */
    protected $socket;

    /**
     * An array of client connections
     *
     * @var array<Connection>
     */
    protected $connections = array();

    /**
     * An array of raw socket resources, corresponding to connections, roughly
     *
     * @var array<resource>
     */
    protected $resources = array();

    /**
     * Constructor
     *
     * @param Server $server
     * @param array $options
     */
    public function __construct(Server $server, array $options = array())
    {
        $this->server = $server;

        parent::__construct($options);
    }

    /**
     * @see WebSocket\Socket.Socket::configure()
     *   Options include:
     *     - timeout_select       => int, seconds, default 5
     *     - timeout_accept       => int, seconds, default 5
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'socket_master_class'   => 'WebSocket\Socket\ServerSocket',
            'socket_master_options' => array(),
            'connection_class'      => 'WebSocket\Connection',
            'connection_options'    => array(),
            'timeout_select'        => self::TIMEOUT_SELECT,

        ), $options);

        parent::configure($options);

        $this->configureSocket();
    }

    /**
     * Configures the main server socket
     *
     * @param string $uri
     */
    protected function configureSocket()
    {
        $class   = $this->options['socket_master_class'];
        $options = $this->options['socket_master_options'];
        $this->socket = new $class($this->server->getUri(), $options);
    }


    public function listen()
    {
        $this->socket->listen();
        $this->resources[] = $this->socket->getResource();
    }

    /**
     * Gets all resources
     */
    protected function getAllResources()
    {
        return array_merge($this->resources, array($this->socket->getResource()));
    }

    protected function getConnectionForClientSocket($socket)
    {
        if (!isset($this->connections[$this->resourceId($socket)])) {
            return false;
        }
        return $this->connections[$this->resourceId($socket)];
    }

    protected function removeSocket($socket)
    {
        unset($this->connections[$client->getResourceId()]);
        $index = array_search($resource, $this->resources);
        unset($this->resources[$index], $client);
    }


    /**
     * Select and process an array of resources
     *
     * @param array $resources
     */
    public function selectAndProcess()
    {
        $read             = $this->resources;
        $unused_write     = null;
        $unsued_exception = null;

        stream_select(
            $read,
            $unused_write,
            $unused_exception,
            $this->options['timeout_select']
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
    protected function processMasterSocket()
    {
        $new = null;

        try {
            $new = $this->socket->accept();
        } catch (Exception $e) {
            $this->server->log('Socket error: ' . $e, 'err');
            return;
        }

        $connection = $this->createConnection($new);
        $this->server->notify(Server::EVENT_SOCKET_CONNECT, array($new, $connection));
    }

    /**
     * Creates a connection from a socket resource
     *
     * @param resource $resource A socket resource
     * @return Connection
     */
    protected function createConnection($resource)
    {
        if (!$resource || !is_resource($resource)) {
            throw new InvalidArgumentException('Invalid connection resource');
        }

        $class = $this->options['connection_class'];
        $options = $this->options['connection_options'];

        $connection = new $class($this, $resource, $options);

        $this->resources[] = $resource;
        $this->connections[$this->resourceId($resource)] = $connection;

        return $connection;
    }

    /**
     * Process events on a client socket
     *
     * @param resource $socket
     */
    protected function processClientSocket($socket)
    {
        $connection = $this->getConnectionForClientSocket($socket);

        if (!$connection) {
            $this->log('No connection for client socket', 'warning');
            return;
        }

        $data = $connection->getSocket()->receive();
        $bytes = strlen($data);

        if ($bytes === 0) {
            $connection->onDisconnect();
            continue;
        } elseif ($data === false) {
            $this->removeClientOnError($connection);
            continue;
        } else {
            $connection->onData($data);
        }
    }

    /**
     * This server makes an explicit assumption: PHP resource types may be cast
     * to a integer. Furthermore, we assume this is bijective. Both seem to be
     * true in most circumstances, but may not be guaranteed.
     *
     * This method (and $this->getResourceId()) exist to make this assumption
     * explicit.
     *
     * @param resource $resource
     */
    protected function resourceId($resource)
    {
        return (int)$resource;
    }

    /**
     * Gets the connection manager's listening URI
     *
     * @return string
     */
    public function getUri()
    {
        return $this->server->getUri();
    }

    public function log($message, $priority = 'info')
    {
        $this->server->log('Manager: ' . $message, $priority);
    }

    /**
     * Removes a client from client storage.
     *
     * @param Object $client Client object.
     * @deprecated
     */
    public function removeClientOnClose($client)
    {
        throw new \Exception('Not implemented');
//         $clientId = $client->getClientId();
//         $clientIp = $client->getClientIp();
//         $clientPort = $client->getClientPort();
//         $resource = $client->getClientSocket();

//         $this->_removeIpFromStorage($client->getClientIp());
//         if(isset($this->_requestStorage[$clientId]))
//         {
//             unset($this->_requestStorage[$clientId]);
//         }
//         unset($this->connections[(int)$resource]);
//         $index = array_search($resource, $this->resources);
//         unset($this->resources[$index], $client);

//         // trigger status application:
//         if($this->getApplication('status') !== false)
//         {
//             $this->getApplication('status')->clientDisconnected($clientIp, $clientPort);
//         }
//         unset($clientId, $clientIp, $clientPort, $resource);
    }

    /**
     * Removes a client and all references in case of timeout/error.
     * @param object $client The client object to remove.
     */
    public function removeClientOnError($client)
    {        // remove reference in clients app:
        if($client->getClientApplication() !== false)
        {
            $client->getClientApplication()->onDisconnect($client);
        }

        $resource = $client->getClientSocket();
        $clientId = $client->getClientId();
        $clientIp = $client->getClientIp();
        $clientPort = $client->getClientPort();
        $this->_removeIpFromStorage($client->getClientIp());
        if(isset($this->_requestStorage[$clientId]))
        {
            unset($this->_requestStorage[$clientId]);
        }
        unset($this->connections[(int)$resource]);


        // trigger status application:
        if($this->getApplication('status') !== false)
        {
            $this->getApplication('status')->clientDisconnected($clientIp, $clientPort);
        }
        unset($resource, $clientId, $clientIp, $clientPort);
    }
}
