------------------------
Wrench\\Payload\\Payload
------------------------

.. php:namespace: Wrench\\Payload

.. php:class:: Payload

    Payload class

    Represents a WebSocket protocol payload, which may be made up of multiple frames.

    .. php:attr:: frames

        A payload may consist of one or more frames

    .. php:method:: getCurrentFrame()

        Gets the current frame for the payload

        :returns: mixed

    .. php:method:: getReceivingFrame()

        Gets the frame into which data should be receieved

        :returns: Frame

    .. php:method:: getFrame()

        Get a frame object

        :returns: Frame

    .. php:method:: isComplete()

        Whether the payload is complete

        :returns: boolean

    .. php:method:: encode(string $data, int $type = , boolean $masked = )

        Encodes a payload

        :param string $data:
        :param int $type:
        :param boolean $masked:
        :returns: Payload

    .. php:method:: getRemainingData()

        Gets the number of remaining bytes before this payload will be
        complete

        May return 0 (no more bytes required) or null (unknown number of bytes
        required).

        :returns: number|NULL

    .. php:method:: isWaitingForData()

        Whether this payload is waiting for more data

        :returns: boolean

    .. php:method:: sendToSocket(Socket $socket)

        :param Socket $socket:
        :returns: boolean

    .. php:method:: receiveData(string $data)

        Receive raw data into the payload

        :param string $data:
        :returns: void

    .. php:method:: getPayload()

        :returns: string

    .. php:method:: __toString()

        :returns: string

    .. php:method:: getType()

        Gets the type of the payload

        The type of a payload is taken from its first frame

        :returns: int
