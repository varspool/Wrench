--------------
Wrench\\Client
--------------

.. php:namespace: Wrench

.. php:class:: Client

    Client class

    Represents a Wrench client

    .. php:const:: MAX_HANDSHAKE_RESPONSE

    .. php:attr:: uri

        protected

    .. php:attr:: origin

        protected

    .. php:attr:: socket

        protected

    .. php:attr:: headers

        protected array

        Request headers

    .. php:attr:: protocol

        protected Protocol

        Protocol instance

    .. php:attr:: options

        protected array

        Options

    .. php:attr:: connected

        protected boolean

        Whether the client is connected

    .. php:method:: __construct($uri, $origin, $options = array())

        Constructor

        :type $uri: string
        :param $uri:
        :type $origin: string
        :param $origin: The origin to include in the handshake (required in later versions of the protocol)
        :param $options:

    .. php:method:: configure($options)

        Configure options

        :type $options: array
        :param $options:
        :returns: void

    .. php:method:: __destruct()

        Destructor

    .. php:method:: addRequestHeader($name, $value)

        Adds a request header to be included in the initial handshake

        For example, to include a Cookie header

        :type $name: string
        :param $name:
        :type $value: string
        :param $value:
        :returns: void

    .. php:method:: sendData($data, $type = 'text', $masked = true)

        Sends data to the socket

        :type $data: string
        :param $data:
        :type $type: string
        :param $type: Payload type
        :type $masked: boolean
        :param $masked:
        :returns: int bytes written

    .. php:method:: connect()

        Connect to the Wrench server

        :returns: boolean Whether a new connection was made

    .. php:method:: isConnected()

        Whether the client is currently connected

        :returns: boolean

    .. php:method:: disconnect()
