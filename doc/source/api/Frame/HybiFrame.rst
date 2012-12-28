------------------------
Wrench\\Frame\\HybiFrame
------------------------

.. php:namespace: Wrench\\Frame

.. php:class:: HybiFrame

    .. php:attr:: masked

        protected boolean

        Whether the payload is masked

    .. php:attr:: mask

        protected string

        Masking key

    .. php:attr:: offset_payload

        protected int

        Byte offsets

    .. php:attr:: offset_mask

        protected

    .. php:attr:: length

        protected int

        The frame data length

    .. php:attr:: type

        protected int

        The type of this payload

    .. php:attr:: buffer

        protected string

        The buffer

        May not be a complete payload, because this frame may still be receiving
        data. See

    .. php:attr:: payload

        protected string

        The enclosed frame payload

        May not be a complete payload, because this frame might indicate a
        continuation frame. See isFinal() versus isComplete()

    .. php:method:: encode($payload, $type = Protocol::TYPE_TEXT, $masked = false)

        :param $payload:
        :param $type:
        :param $masked:

    .. php:method:: mask($payload)

        Masks/Unmasks the frame

        :type $payload: string
        :param $payload:
        :returns: string

    .. php:method:: unmask($payload)

        Masks a payload

        :type $payload: string
        :param $payload:
        :returns: string

    .. php:method:: receiveData($data)

        :param $data:

    .. php:method:: getMask()

        Gets the mask

        :returns: string

    .. php:method:: generateMask()

        Generates a suitable masking key

        :returns: string

    .. php:method:: isMasked()

        Whether the frame is masked

        :returns: boolean

    .. php:method:: getExpectedBufferLength()

    .. php:method:: getPayloadOffset()

        Gets the offset of the payload in the frame

        :returns: int

    .. php:method:: getMaskOffset()

        Gets the offset in the frame to the masking bytes

        :returns: int

    .. php:method:: getLength()

    .. php:method:: getInitialLength()

        Gets the inital length value, stored in the first length byte

        This determines how the rest of the length value is parsed out of the
        frame.

        :returns: int

    .. php:method:: getLengthSize()

        Returns the byte size of the length part of the frame

        Not including the initial 7 bit part

        :returns: int

    .. php:method:: getMaskSize()

        Returns the byte size of the mask part of the frame

        :returns: int

    .. php:method:: decodeFramePayloadFromBuffer()

    .. php:method:: isFinal()

    .. php:method:: getType()

    .. php:method:: isComplete()

        Whether the frame is complete

        :returns: boolean

    .. php:method:: getRemainingData()

        Gets the remaining number of bytes before this frame will be complete

        :returns: number

    .. php:method:: isWaitingForData()

        Whether this frame is waiting for more data

        :returns: boolean

    .. php:method:: getFramePayload()

        Gets the contents of the frame payload

        The frame must be complete to call this method.

        :returns: string

    .. php:method:: getFrameBuffer()

        Gets the contents of the frame buffer

        This is the encoded value, receieved into the frame with recieveData().

        :returns: string binary

    .. php:method:: getBufferLength()

        Gets the expected length of the frame payload

        :returns: int
