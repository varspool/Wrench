<?php

namespace Wrench\Payload;

use Wrench\Socket\Socket;

/**
 * Payload class
 *
 * Represents a WebSocket protocol payload, which may be made up of multiple
 * frames.
 */
abstract class Payload
{
    /**
     * A payload may consist of one or more frames
     *
     * @var array<Frame>
     */
    protected $frames = array();

    /**
     * Gets the current frame for the payload
     *
     * @return mixed
     */
    protected function getCurrentFrame()
    {
        if (empty($this->frames)) {
            array_push($this->frames, $this->getFrame());
        }
        return end($this->frames);
    }

    /**
     * Gets the frame into which data should be receieved
     *
     * @throws PayloadException
     * @return Frame
     */
    protected function getReceivingFrame()
    {
        $current = $this->getCurrentFrame();

        if ($current->isComplete()) {
            if ($current->isFinal()) {
                throw new PayloadException('Payload cannot receieve data: it is already complete');
            } else {
                $current = array_push($this->frames, $this->getFrame());
            }
        }

        return $current;
    }

    /**
     * Get a frame object
     *
     * @return Frame
     */
    abstract protected function getFrame();

    /**
     * Whether the payload is complete
     *
     * @return boolean
     */
    public function isComplete()
    {
        return $this->getCurrentFrame()->isComplete() && $this->getCurrentFrame()->isFinal();
    }

    /**
     * Encodes a payload
     *
     * @param string $data
     * @param int $type
     * @param boolean $masked
     * @return Payload
     * @todo No splitting into multiple frames just yet
     */
    public function encode($data, $type = Protocol::TYPE_TEXT, $masked = false)
    {
        $this->frames = array();

        $frame = $this->getFrame();
        array_push($this->frames, $frame);

        $frame->encode($data, $type, $masked);

        return $this;
    }

    /**
     * @param Socket $socket
     * @return boolean
     */
    public function sendToSocket(Socket $socket)
    {
        $success = true;
        foreach ($this->frames as $frame) {
            $success = $success && ($socket->send($frame->getFrameBuffer()) !== false);
        }
        return $success;
    }

    /**
     * Receive raw data into the payload
     *
     * @param string $data
     */
    public function receiveData($data)
    {
        $frame = $this->getReceivingFrame();
        $frame->receiveData($data);
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        $this->buffer = '';

        foreach ($this->frames as $frame) {
            $this->buffer .= $frame->getFramePayload();
        }

        return $this->buffer;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getPayload();
    }

    /**
     * Gets the type of the payload
     *
     * The type of a payload is taken from its first frame
     *
     * @throws PayloadException
     * @return int
     */
    public function getType()
    {
        if (!isset($this->frames[0])) {
            throw new PayloadException('Cannot tell payload type yet');
        }
        return $this->frames[0]->getType();
    }
}