<?php
namespace WebSocket;

use WebSocket\Socket;
use WebSocket\Resource;

use \Closure;
use \InvalidArgumentException;

/**
 * WebSocket server
 *
 * The server extends socket, which provides the master socket resource. This
 * resource is listened to, and an array of clients managed.
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Simon Samtleben <web@lemmingzshadow.net>
 * @author Dominic Scheirlinck <dominic@varspool.com>
 */
class Server extends Socket
{
    /**#@+
     * Events
     *
     * @var string
     */
    const EVENT_SOCKET_CONNECT = 'socket_connect';
    const EVENT_SOCKET_DISCONNECT = 'socket_disconnect';
    const EVENT_CLIENT_CONNECT = 'client_connect';
    const EVENT_CLIENT_DISCONNECT = 'client_disconnect';
    /**#@-*/

    /**
     * Options
     *
     * @var array
     */
    protected $options = array();

    /**
     * A logging callback
     *
     * The default callback simply prints to stdout. You can pass your own logger
     * in the options array. It should take a string message and string priority
     * as parameters.
     *
     * @var Closure
     */
    protected $logger;

    /**
     * Event listeners
     *
     * Add listeners using the addListener() method.
     *
     * @var array<string => array<Closure>>
     */
    protected $listeners = array();

    /**
     * Holds all connected sockets
     *
     * @var array
     */
    protected $resources = array();


    /**
     * An array of client connections
     *
     * @var array<Connection>
     */
    protected $connections = array();


    protected $applications = array();
    private $_ipStorage = array();
    private $_requestStorage = array();

    // server settings:
    private $_checkOrigin = true;
    private $_allowedOrigins = array();
    private $_maxClients = 30;
    private $_maxConnectionsPerIp = 5;
    private $_maxRequestsPerMinute = 50;


    /**
     * Constructor
     *
     * @param string $uri Websocket URI, e.g. ws://localhost:8000/, path will
     *                     be ignored
     * @param array $options (optional)
     *   Options:
     *     - logger               => Closure($message, $priority = 'info'), used
     *                                 for logging
     *     - timeout_select       => int, seconds, default 5
     *     - timeout_accept       => int, seconds, default 5
     *
     *   This object also accepts all the options of Socket:
     *     - protocol             => WebSocket\Protocol object, latest protocol
     *                                 version used if not specified
     *     - timeout_connect      => int, seconds, default 2
     *     - timeout_socket       => int, seconds, default 5
     *     - backlog              => int, used to limit the number of outstanding
     *                                 connections in the socket's listen queue
     *     - server_ssl_cert_file => string, server SSL certificate
     *                                 file location. File should contain
     *                                 certificate and private key
     *     - server_ssl_passphrase => string, passphrase for the key
     *     - server_ssl_allow_self_signed => boolean, whether to allows self-
     *                                 signed certs
     */
    public function __construct($uri, array $options = array())
    {
        parent::__construct($uri, $options);

        $this->listeners = array(
            self::EVENT_CLIENT_CONNECT => array(),
            self::EVENT_CLIENT_DISCONNECT => array()
        );

        $this->log('Server initialized', 'info');
    }

    /**
     * Sets a logger
     *
     * @param Closure $logger
     */
    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Main server loop
     *
     * This method does not return.
     */
    public function run()
    {
        $this->listen();
        while (true) {
            /*
             * If there's nothing changed on any of the sockets, the server
             * will sleep for up to timeout_socket and
             */
            $this->processSockets();
        }
    }

    /**
     * Logs a message to the server log
     *
     * The default logger simply prints the message to stdout. You can provide
     * a logging closure. This is useful, for instance, if you've daemonized
     * and closed STDOUT.
     *
     * @param string $message Message to display.
     * @param string $type Type of message.
     * @return void
     */
    public function log($message, $priority = 'info')
    {
        $log = $this->logger;
        $log($message, $priority);
    }

    /**
     * Configures options
     *
     * @return void
     */
    protected function configure(array $options)
    {
        $options = array_merge($options, array(
            'timeout_accept' => 5,
            'timeout_select' => 5,
        ), $options);

        parent::configure($options);

        // Default logger
        if (!isset($this->options['logger'])) {
            $this->options['logger'] = function ($message, $priority = 'info') {
                printf("%s: %s%s", $priority, $message, PHP_EOL);
            };
        }
        $this->setLogger($this->options['logger']);
    }

    /**
     * Gets all resources
     */
    protected function getAllResources()
    {
        return array_merge($this->resources, array($this->getResource()));
    }

    /**
     * Selects all active sockets for read events, and processes those whose
     * state changes. Listens for connections, handles connects/disconnects, e.g.
     *
     * @return void
     */
    protected function processSockets()
    {
        $changed_sockets = $this->resources;
        $write     = null;
        $exception = null;

        stream_select(
            $changed_sockets,
            $write,
            $exception,
            $this->options['timeout_select']
        );

        foreach ($changed_sockets as $socket) {
            if ($socket == $this->socket) {
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
        $new = stream_socket_accept(
            $this->socket,
            $this->options['timeout_accept']
        );

        if ($new === false) {
            $this->log('Socket error: ' . socket_strerror(socket_last_error($new)));
            return;
        }

        $connection = $this->createConnection($new);
        $this->notify(self::EVENT_CLIENT_CONNECT, array($connection));
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

        $connection->receive();

        $data = $this->read($socket);
        $bytes = strlen($data);

        if ($bytes === 0) {
            $connection->onDisconnect();
            continue;
        } elseif ($data === false) {
            $this->removeClientOnError($connection);
            continue;
        } elseif($connection->waitingForData === false && $this->_checkRequestLimit($connection->getClientId()) === false) {
            $connection->onDisconnect();
        } else {
            $connection->onData($data);
        }
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
     * Notifies listeners of an event
     *
     * @param string $event
     * @param array $arguments Event arguments
     */
    protected function notify($event, array $arguments = array())
    {
        foreach ($this->listeners[$event] as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }

    /**
     * Adds a listener
     *
     * Provide an event (see the Server::EVENT_* constants) and a callback
     * closure. Some arguments may be provided to your callback, such as the
     * connection the caused the event.
     *
     * @param string $event
     * @param Closure $callback
     * @throws InvalidArgumentException
     */
    public function addListener($event, Closure $callback)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }

        if (!($callback instanceof Closure)) {
            throw new InvalidArgumentException('Invalid listener');
        }

        $this->listeners[$event][] = $callback;
    }

    /**
     * Creates a connection from a socket resource
     *
     * @param resource $resource A socket resource
     * @return Connection
     */
    protected function createConnection($resource)
    {
        $this->resources[] = $resource;
        return (($this->connections[(int)$resource] = new Connection($this, $resource)));
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
     * Returns a server application.
     *
     * @param string $key Name of application.
     * @return object The application object.
     */
    public function getApplication($key)
    {
        if(empty($key))
        {
            return false;
        }
        if(array_key_exists($key, $this->applications))
        {
            return $this->applications[$key];
        }
        return false;
    }

    /**
     * Adds a new application object to the application storage.
     *
     * @param string $key Name of application.
     * @param object $application The application object.
     */
    public function registerApplication($key, $application)
    {
        $this->applications[$key] = $application;
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

    protected function listen()
    {
        parent::listen();

        $this->resources[] = $this->socket;
    }
}
