<?php
namespace WebSocket;

use WebSocket\Util\Configurable;

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
class Server extends Configurable
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
     * The URI of the server
     *
     * @var string
     */
    protected $uri;

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
     * An array of client connections
     *
     * @var array<Connection>
     */
    protected $connections = array();

    protected $connectionManager;


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
     * @param array $options (optional) See configure
     */
    public function __construct($uri, array $options = array())
    {
        $this->uri = $uri;

        parent::__construct($options);

        $this->log('Server initialized', 'info');
    }

    /**
     * Configure options
     *
     * Options include
     *   - socket_class      => The socket class to use, defaults to ServerSocket
     *   - socket_options    => An array of socket options
     *   - logger            => Closure($message, $priority = 'info'), used
     *                                 for logging
     *
     * @param array $options
     * @return void
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'connection_manager_class'   => 'WebSocket\ConnectionManager',
            'connection_manager_options' => array()
        ), $options);

        parent::configure($options);

        $this->configureConnectionManager();
        $this->configureLogger();
    }

    /**
     * Configures the logger
     */
    protected function configureLogger()
    {
        // Default logger
        if (!isset($this->options['logger'])) {
            $this->options['logger'] = function ($message, $priority = 'info') {
                printf("%s: %s%s", $priority, $message, PHP_EOL);
            };
        }
        $this->setLogger($this->options['logger']);
    }

    /**
     * Configures the connection manager
     */
    protected function configureConnectionManager()
    {
        $class   = $this->options['connection_manager_class'];
        $options = $this->options['connection_manager_options'];
        $this->connectionManager = new $class($this, $options);
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
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
        $this->connectionManager->listen();

        while (true) {
            /*
             * If there's nothing changed on any of the sockets, the server
             * will sleep and other processes will have a change to run. Control
             * this behaviour with the timeout options.
             */
            $this->connectionManager->selectAndProcess();
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
     * Notifies listeners of an event
     *
     * @param string $event
     * @param array $arguments Event arguments
     */
    public function notify($event, array $arguments = array())
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

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
     * Whether the socket is currently connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
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
}
