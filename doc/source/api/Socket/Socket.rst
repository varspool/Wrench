----------------------
Wrench\\Socket\\Socket
----------------------

.. php:namespace: Wrench\\Socket

.. php:class:: Socket

    Socket class

    Implements low level logic for connecting, serving, reading to, and writing from WebSocket connections using PHP's streams.

    Unlike in previous versions of this library, a Socket instance now represents a single underlying socket resource. It's designed to be used by aggregation, rather than inheritence.

    .. php:const:: TIMEOUT_SOCKET

        Default timeout for socket operations (reads, writes)

    .. php:const:: DEFAULT_RECEIVE_LENGTH

    .. php:const:: NAME_PART_IP

        Socket name parts

    .. php:attr:: socket

        protected resource

    .. php:attr:: context

        protected

        Stream context

    .. php:attr:: connected

        protected boolean

        Whether the socket is connected to a server

        Note, the connection may not be ready to use, but the socket is connected
        at least. See $handshaked, and other properties in subclasses.

    .. php:attr:: firstRead

        protected boolean

        Whether the current read is the first one to the socket

    .. php:attr:: name

        protected string

        The socket name according to stream_socket_get_name

    .. php:attr:: options

        protected array

    .. php:attr:: protocol

        protected Protocol

    .. php:method:: configure($options)

        Configure options

        Options include
        - timeout_connect      => int, seconds, default 2
        - timeout_socket       => int, seconds, default 5

        :type $options: array
        :param $options:
        :returns: void

    .. php:method:: getName()

        Gets the name of the socket

    .. php:method:: getNamePart($name, $part)

        Gets part of the name of the socket

        PHP seems to return IPV6 address/port combos like this:
        ::1:1234, where ::1 is the address and 1234 the port So, the part number
        here is either the last : delimited section (the port)
        or all the other sections (the whole initial part, the address).

        :type $name: string
        :param $name: (from $this->getName() usually)
        :param $part:
        :returns: string

    .. php:method:: getIp()

        Gets the IP address of the socket

        :returns: string

    .. php:method:: getPort()

        Gets the port of the socket

        :returns: int

    .. php:method:: getLastError()

        Get the last error that occurred on the socket

        :returns: int|string

    .. php:method:: isConnected()

        Whether the socket is currently connected

        :returns: boolean

    .. php:method:: disconnect()

        Disconnect the socket

        :returns: void

    .. php:method:: getResource()

    .. php:method:: getResourceId()

    .. php:method:: send($data)

        :type $data: unknown_type
        :param $data:
        :returns: boolean|int The number of bytes sent or false on error

    .. php:method:: receive($length = self::DEFAULT_RECEIVE_LENGTH)

        Recieve data from the socket

        :type $length: int
        :param $length:
        :returns: string

    .. php:method:: __construct($options = array())

        Configurable constructor

        :param $options:

    .. php:method:: configureProtocol()

        Configures the protocol option
