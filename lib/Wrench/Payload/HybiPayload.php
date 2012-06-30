<?php

namespace Wrench\Payload;

use Wrench\Payload\Payload;

class HybiPayload extends Payload
{
    // First byte
    const BITFIELD_FINAL = 0x80;
    const BITFIELD_RSV1  = 0x40;
    const BITFIELD_RSV2  = 0x20;
    const BITFIELD_RSV3  = 0x10;
    const BITFIELD_TYPE  = 0x0f;

    // Second byte
    const BITFIELD_MASKED = 0x80;
    const BITFIELD_LENGTH = 0x7f;

    protected $offset_initial = 2;
    protected $offset_payload = null;

    public function decode($encoded)
    {
        $this->final = (boolean)(ord($encoded[0]) & self::BITFIELD_FINAL);
        $this->type  = (int)(ord($encoded[0]) & self::BITFIELD_TYPE);

        $this->validateType($this->type);

        $this->parseLength($encoded);
        $this->parseMask();

        if (strlen($encoded) < $this->getExpectedDataLength()) {
            // Not all data has arrived
            return false;
        }

        $this->parsePayload($encoded);
    }

    public function encode($data, $type = self::TYPE_TEXT, $masked = false)
    {
        $this->validateType($type);

        die('not yet implemented');
    }

    /**
     * Unmasks the payload
     *
     * @param string $payload
     */
    protected function unmask($payload)
    {
        $length = strlen($payload);

        $unmasked = '';
        for ($i = 0; $i < $length; $i++) {
            $unmasked .= $payload[$i] ^ $this->mask[$i % 4];
        }
        return $unmasked;
    }

    protected function getExpectedDataLength()
    {
        return $this->length + $this->offset_payload;
    }

    protected function parseLength($encoded)
    {
        $length = (int)(ord($encoded[1]) & self::BITFIELD_LENGTH);

        if ($length < 126) {
            $this->length = $length;
            return;
        } elseif ($length === 126) {
            $this->offset_inital = 4;

            $this->length   = ord($encoded[2]);
            $this->length <<= 8;
            $this->length  += ord($encoded[3]);
        } elseif ($length === 127) {
            $this->offset_inital = 10;

            $this->length = 0;
            for ($i = 2; $i <= 10; $i++) {
                $this->length <<= 8;
                $this->length  += ord($encoded[$i]);
            }
        }
    }

    protected function parseMask($encoded)
    {
        $this->masked = (boolean)(ord($encoded[1]) & self::BITFIELD_MASKED);

        if ($this->masked) {
            $this->offset_payload = $this->offset_initial + 4;
            $this->mask = substr($encoded, $this->offset_initial, 4);
        } else {
            $this->offset_payload = $this->offset_initial;
        }
    }

    protected function parsePayload($encoded)
    {
        if ($this->masked) {
            return $this->unmask(substr($encoded, $this->offset_payload));
        } else {
            return substr($encoded, $this->offset_payload);
        }
    }


    protected function validateType($type)
    {
        throw new PayloadException('Invalid payload type');
    }
}
