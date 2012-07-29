----------------------------------
Wrench\\Socket\\ServerClientSocket
----------------------------------

.. php:namespace: Wrench\\Socket

.. php:class:: ServerClientSocket

    .. php:const:: TIMEOUT_SOCKET

        Default timeout for socket operations (reads, writes)

    .. php:const:: DEFAULT_RECEIVE_LENGTH

    .. php:const:: NAME_PART_IP

        Socket name parts

    .. php:attr:: socket

    .. php:attr:: context

        Stream context

    .. php:attr:: connected

        Whether the socket is connected to a server

        Note, the connection may not be ready to use, but the socket is connected
        at least. See $handshaked, and other properties in subclasses.

    .. php:attr:: firstRead

        Whether the current read is the first one to the socket

    .. php:attr:: name

        The socket name according to stream_socket_get_name

    .. php:attr:: options

    .. php:attr:: protocol

    .. php:method:: __construct(resource $accepted_socket, array $options = Array)

        Constructor

        A server client socket is accepted from a listening socket, so there's no
        need to call ->connect() or whatnot.

        :param resource $accepted_socket:
        :param array $options:

    .. php:method:: configure(array $options)

        Configure options

        Options include
        - timeout_connect      => int, seconds, default 2
        - timeout_socket       => int, seconds, default 5

        :param array $options:
        :returns: void

    .. php:method:: getName()

        Gets the name of the socket

    .. php:method:: getNamePart(string $name, $part)

        Gets part of the name of the socket

        PHP seems to return IPV6 address/port combos like this:
        ::1:1234, where ::1 is the address and 1234 the port So, the part number
        here is either the last : delimited section (the port)
        or all the other sections (the whole initial part, the address).

        :param string $name: (from $this->getName() usually)
        :param unknown $part:
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

    .. php:method:: send(unknown_type $data)

        :param unknown_type $data:
        :returns: boolean|int The number of bytes sent or false on error

    .. php:method:: receive(int $length = 1400)

        Recieve data from the socket

        :param int $length:
        :returns: string

    .. php:method:: configureProtocol()

        Configures the protocol option
