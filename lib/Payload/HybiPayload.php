<?php

namespace Wrench\Payload;

use Wrench\Frame\Frame;
use Wrench\Frame\HybiFrame;

/**
 * Gets a HyBi payload
 */
class HybiPayload extends Payload
{
    protected function getFrame(): Frame
    {
        return new HybiFrame();
    }
}
