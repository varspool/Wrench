--------------------------------
Wrench\\Application\\Application
--------------------------------

.. php:namespace: Wrench\\Application

.. php:class:: Application

    Wrench Server Application

    .. php:method:: onData($payload, $connection)

        Handle data received from a client

        :type $payload: Payload
        :param $payload: A payload object, that supports __toString()
        :type $connection: Connection
        :param $connection:
