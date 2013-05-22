------------------
Wrench\\Connection
------------------

.. php:namespace: Wrench

.. php:class:: Connection

    Represents a client connection on the server side

    i.e. the `Server` manages a bunch of `Connection`s

    .. php:attr:: manager

        protected Wrench\ConnectionManager

        The connection manager

    .. php:attr:: socket

        protected Socket

        Socket object

        Wraps the client connection resource

    .. php:attr:: handshaked

        protected boolean

        Whether the connection has successfully handshaken

    .. php:attr:: application

        protected Application

        The application this connection belongs to

    .. php:attr:: ip

        protected string

        The IP address of the client

    .. php:attr:: port

        protected int

        The port of the client
        
    .. php:attr:: headers

        protected array

        The array of headers included with the original request (like Cookie for example).
        The headers specific to the web sockets handshaking have been stripped out.
        
    .. php:attr:: queryParams

        protected array

        The array of query parameters included in the original request.
        The array is in the format 'key' => 'value'.

    .. php:attr:: id

        protected string|null

        Connection ID

    .. php:attr:: payload

        protected

        The current payload

    .. php:attr:: options

        protected array

    .. php:attr:: protocol

        protected Protocol

    .. php:method:: __construct(ConnectionManager $manager, ServerClientSocket $socket, $options = array())

        Constructor

        :type $manager: ConnectionManager
        :param $manager:
        :type $socket: ServerClientSocket
        :param $socket:
        :type $options: array
        :param $options:

    .. php:method:: getConnectionManager()

        Gets the connection manager of this connection

        :returns: \Wrench\ConnectionManager

    .. php:method:: configure($options)

        :param $options:

    .. php:method:: configureClientInformation()

    .. php:method:: configureClientId()

        Configures the client ID

        We hash the client ID to prevent leakage of information if another client
        happens to get a hold of an ID. The secret *must* be lengthy, and must be
        kept secret for this to work: otherwise it's trivial to search the space
        of possible IP addresses/ports (well, if not trivial, at least very fast).

    .. php:method:: onData($data)

        Data receiver

        Called by the connection manager when the connection has received data

        :type $data: string
        :param $data:

    .. php:method:: handshake($data)

        Performs a websocket handshake

        :type $data: string
        :param $data:

    .. php:method:: export($data)

        Returns a string export of the given binary data

        :type $data: string
        :param $data:
        :returns: string

    .. php:method:: handle($data)

        Handle data received from the client

        The data passed in may belong to several different frames across one or
        more protocols. It may not even contain a single complete frame. This
        method manages slotting the data into separate payload objects.

        :type $data: string
        :param $data:

    .. php:method:: handlePayload(Payload $payload)

        Handle a complete payload received from the client

        :type $payload: Payload
        :param $payload:

    .. php:method:: send($data, $type = Protocol::TYPE_TEXT)

        Sends the payload to the connection

        :param $data:
        :type $type: string
        :param $type:
        :returns: boolean

    .. php:method:: process()

        Processes data on the socket

    .. php:method:: close($code = Protocol::CLOSE_NORMAL)

        Closes the connection according to the WebSocket protocol

        :param $code:
        :returns: boolean

    .. php:method:: log($message, $priority = 'info')

        Logs a message

        :type $message: string
        :param $message:
        :type $priority: string
        :param $priority:

    .. php:method:: getIp()

        Gets the IP address of the connection

        :returns: string Usually dotted quad notation

    .. php:method:: getPort()

        Gets the port of the connection

        :returns: int
        
    .. php:method:: getHeaders()

        Gets the non-web-sockets headers included with the original request

        :returns: array
        
    .. php:method:: getQueryParams()

        Gets the query parameters included with the original request

        :returns: array

    .. php:method:: getId()

        Gets the connection ID

        :returns: string

    .. php:method:: getSocket()

        Gets the socket object

        :returns: Socket\ServerClientSocket

    .. php:method:: getClientApplication()

        Gets the client application

        :returns: Application

    .. php:method:: configureProtocol()

        Configures the protocol option
