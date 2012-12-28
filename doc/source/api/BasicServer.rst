-------------------
Wrench\\BasicServer
-------------------

.. php:namespace: Wrench

.. php:class:: BasicServer

    .. php:const:: EVENT_SOCKET_CONNECT

        Events

    .. php:attr:: rateLimiter

        protected

    .. php:attr:: originPolicy

        protected

    .. php:attr:: uri

        protected string

        The URI of the server

    .. php:attr:: options

        protected array

        Options

    .. php:attr:: logger

        protected Closure

        A logging callback

        The default callback simply prints to stdout. You can pass your own logger
        in the options array. It should take a string message and string priority
        as parameters.

    .. php:attr:: listeners

        protected array<string

        Event listeners

        Add listeners using the addListener() method.

    .. php:attr:: connectionManager

        protected ConnectionManager

        Connection manager

    .. php:attr:: applications

        protected array<string

        Applications

    .. php:attr:: protocol

        protected Protocol

    .. php:method:: __construct($uri, $options = array())

        Constructor

        :type $uri: string
        :param $uri:
        :type $options: array
        :param $options:

    .. php:method:: configure($options)

        :param $options:

    .. php:method:: configureRateLimiter()

    .. php:method:: configureOriginPolicy()

        Configures the origin policy

    .. php:method:: addAllowedOrigin($origin)

        Adds an allowed origin

        :type $origin: array
        :param $origin:

    .. php:method:: configureLogger()

        Configures the logger

        :returns: void

    .. php:method:: configureConnectionManager()

        Configures the connection manager

        :returns: void

    .. php:method:: getConnectionManager()

        Gets the connection manager

        :returns: \Wrench\ConnectionManager

    .. php:method:: getUri()

        :returns: string

    .. php:method:: setLogger($logger)

        Sets a logger

        :type $logger: Closure
        :param $logger:
        :returns: void

    .. php:method:: run()

        Main server loop

        :returns: void This method does not return!

    .. php:method:: log($message, $priority = 'info')

        Logs a message to the server log

        The default logger simply prints the message to stdout. You can provide a
        logging closure. This is useful, for instance, if you've daemonized and
        closed STDOUT.

        :type $message: string
        :param $message: Message to display.
        :param $priority:
        :returns: void

    .. php:method:: notify($event, $arguments = array())

        Notifies listeners of an event

        :type $event: string
        :param $event:
        :type $arguments: array
        :param $arguments: Event arguments
        :returns: void

    .. php:method:: addListener($event, $callback)

        Adds a listener

        Provide an event (see the Server::EVENT_* constants) and a callback
        closure. Some arguments may be provided to your callback, such as the
        connection the caused the event.

        :type $event: string
        :param $event:
        :type $callback: Closure
        :param $callback:
        :returns: void

    .. php:method:: getApplication($key)

        Returns a server application.

        :type $key: string
        :param $key: Name of application.
        :returns: Application The application object.

    .. php:method:: registerApplication($key, $application)

        Adds a new application object to the application storage.

        :type $key: string
        :param $key: Name of application.
        :type $application: object
        :param $application: The application object
        :returns: void

    .. php:method:: configureProtocol()

        Configures the protocol option
