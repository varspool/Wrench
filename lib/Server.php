<?php

namespace Wrench;

use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wrench\Application\ConnectionHandlerInterface;
use Wrench\Application\DataHandlerInterface;
use Wrench\Application\UpdateHandlerInterface;
use Wrench\Util\Configurable;
use Wrench\Util\LoopInterface;
use Wrench\Util\NullLoop;

/**
 * WebSocket server
 * The server extends socket, which provides the master socket resource. This
 * resource is listened to, and an array of clients managed.
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Simon Samtleben <web@lemmingzshadow.net>
 * @author Dominic Scheirlinck <dominic@varspool.com>
 */
class Server extends Configurable implements LoggerAwareInterface
{

    use LoggerAwareTrait {
        setLogger as private traitSetLogger;
    }

    /**#@+
     * Events
     *
     * @var string
     */
    const EVENT_SOCKET_CONNECT = 'socket_connect';
    const EVENT_SOCKET_DISCONNECT = 'socket_disconnect';
    const EVENT_HANDSHAKE_REQUEST = 'handshake_request';
    const EVENT_HANDSHAKE_SUCCESSFUL = 'handshake_successful';
    const EVENT_CLIENT_DATA = 'client_data';
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
    protected $options = [];

    /**
     * Event listeners
     * Add listeners using the addListener() method.
     *
     * @var array<string => array<Closure>>
     */
    protected $listeners = [];

    /**
     * Connection manager
     *
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Applications
     *
     * @var array<string => Application>
     */
    protected $applications = [];

    /**
     * Constructor
     *
     * @param string $uri     Websocket URI, e.g. ws://localhost:8000/, path will
     *                        be ignored
     * @param array  $options (optional) See configure
     */
    public function __construct($uri, array $options = [])
    {
        $this->uri = $uri;
        $this->logger = new NullLogger();
        $this->loop = new NullLoop();

        parent::__construct($options);
    }

    /**
     * Gets the connection manager
     *
     * @return ConnectionManager
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    public function setLoop(LoopInterface $loop): void
    {
        $this->loop = $loop;
    }

    /**
     * Main server loop
     *
     * @return void This method does not return!
     */
    public function run(): void
    {
        $this->connectionManager->listen();

        while ($this->loop->shouldContinue()) {
            /*
             * If there's nothing changed on any of the sockets, the server
             * will sleep and other processes will have a change to run. Control
             * this behaviour with the timeout options.
             */
            $this->connectionManager->selectAndProcess();

            /*
             * If the application wants to perform periodic operations or queries
             * and push updates to clients based on the result then that logic can
             * be implemented in the 'onUpdate' method.
             */
            foreach ($this->applications as $application) {
                if (method_exists($application, 'onUpdate')) {
                    $application->onUpdate();
                }
            }
        }
    }

    /**
     * Notifies listeners of an event
     *
     * @param string $event
     * @param array  $arguments Event arguments
     * @return void
     */
    public function notify($event, array $arguments = []): void
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
     * Provide an event (see the Server::EVENT_* constants) and a callback
     * closure. Some arguments may be provided to your callback, such as the
     * connection the caused the event.
     *
     * @param string   $event
     * @param callable $callback
     * @return void
     * @throws InvalidArgumentException
     */
    public function addListener($event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid listener');
        }

        $this->listeners[$event][] = $callback;
    }

    /**
     * Returns a server application.
     *
     * @param string $key Name of application.
     * @return null|DataHandlerInterface|ConnectionHandlerInterface|UpdateHandlerInterface The application object
     */
    public function getApplication($key)
    {
        if (empty($key)) {
            return null;
        }

        if (array_key_exists($key, $this->applications)) {
            return $this->applications[$key];
        }

        return null;
    }

    /**
     * Adds a new application object to the application storage.
     *
     * @param string                                                                 $key         Name of application.
     * @param DataHandlerInterface|ConnectionHandlerInterface|UpdateHandlerInterface $application The application object
     * @return void
     */
    public function registerApplication($key, $application)
    {
        $this->applications[$key] = $application;
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
    protected function configure(array $options): void
    {
        $options = array_merge([
            'connection_manager' => null,
            'connection_manager_class' => ConnectionManager::class,
            'connection_manager_options' => [],
        ], $options);

        parent::configure($options);

        $this->configureConnectionManager();

        if (isset($options['logger'])) {
            $this->setLogger($options['logger']);
        }
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        // calling "parent" setLogger
        $this->traitSetLogger($logger);

        $this->connectionManager->setLogger($logger);
    }

    /**
     * Configures the connection manager
     *
     * @return void
     */
    protected function configureConnectionManager(): void
    {
        if ($this->options['connection_manager']) {
            $this->connectionManager = $this->options['connection_manager'];
        } else {
            $class = $this->options['connection_manager_class'];
            $options = $this->options['connection_manager_options'];

            $this->connectionManager = new $class($this, $options);
        }

        $this->connectionManager->setLogger($this->logger);
    }
}
