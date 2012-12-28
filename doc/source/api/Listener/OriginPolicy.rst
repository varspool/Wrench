------------------------------
Wrench\\Listener\\OriginPolicy
------------------------------

.. php:namespace: Wrench\\Listener

.. php:class:: OriginPolicy

    .. php:attr:: allowed

        protected

    .. php:method:: __construct($allowed)

        :param $allowed:

    .. php:method:: onHandshakeRequest(Connection $connection, $path, $origin, $key, $extensions)

        Handshake request listener

        Closes the connection on handshake from an origin that isn't allowed

        :type $connection: Connection
        :param $connection:
        :type $path: string
        :param $path:
        :type $origin: string
        :param $origin:
        :type $key: string
        :param $key:
        :type $extensions: array
        :param $extensions:

    .. php:method:: isAllowed($origin)

        Whether the specified origin is allowed under this policy

        :type $origin: string
        :param $origin:
        :returns: boolean

    .. php:method:: listen(Server $server)

        :type $server: Server
        :param $server:
