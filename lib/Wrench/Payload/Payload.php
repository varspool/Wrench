<?php

namespace Wrench\Payload;

abstract class Payload
{
    /**#@+
     * Payload types
     *
     *  %x0 denotes a continuation frame
     *  %x1 denotes a text frame
     *  %x2 denotes a binary frame
     *  %x3-7 are reserved for further non-control frames
     *  %x8 denotes a connection close
     *  %x9 denotes a ping
     *  %xA denotes a pong
     *  %xB-F are reserved for further control frames
     *
     * @var int
     */
    const TYPE_CONTINUATION = 0;
    const TYPE_TEXT         = 1;
    const TYPE_BINARY       = 2;
    const TYPE_RESERVED_3   = 3;
    const TYPE_RESERVED_4   = 4;
    const TYPE_RESERVED_5   = 5;
    const TYPE_RESERVED_6   = 6;
    const TYPE_RESERVED_7   = 7;
    const TYPE_CLOSE        = 8;
    const TYPE_PING         = 9;
    const TYPE_PONG         = 10;
    const TYPE_RESERVED_11  = 11;
    const TYPE_RESERVED_12  = 12;
    const TYPE_RESERVED_13  = 13;
    const TYPE_RESERVED_14  = 14;
    const TYPE_RESERVED_15  = 15;
    /**#@-*/

    /**
     * Payload types
     *
     * @var array
     */
    protected $payloadTypes = array(
        'continuation' => self::TYPE_CONTINUATION,
        'text'         => self::TYPE_TEXT,
        'binary'       => self::TYPE_BINARY,
        'close'        => self::TYPE_CLOSE,
        'ping'         => self::TYPE_PING,
        'pong'         => self::TYPE_PONG
    );

    /**
     * The type of this payload
     */
    protected $type = null;

    /**
     * Whether the payload is masked
     *
     * @var boolean
     */
    protected $masked = false;

    /**
     * Masking key
     *
     * @var string
     */
    protected $mask = null;

    /**
     * Whether this is the final payload in a series
     *
     * @var boolean
     */
    protected $final = true;

    /**
     * The payload data length
     *
     * @var int
     */
    protected $length = null;

    /**
     * Encodes a payload
     *
     * @param string $data
     * @param int $type
     * @param boolean $masked
     */
    abstract protected function encode($data, $type = self::TYPE_TEXT, $masked = false);

    /**
     * Decodes a payload
     *
     * @param string $encoded
     */
    abstract protected function decode($encoded);

    /**
     * Whether the frame is masked
     *
     * @return boolean
     */
    public function isMasked()
    {
        return $this->masked;
    }
}