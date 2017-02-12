<?php

namespace Wrench\Tests\Frame;

use Wrench\Tests\Test;

class BadSubclassFrameTest extends Test
{
    /**
     * @expectedException Wrench\Exception\FrameException
     */
    public function testInvalidFrameBuffer()
    {
        $frame = new BadSubclassFrame();
        $frame->getFrameBuffer();
    }

    protected function getClass()
    {
        return 'Wrench\Tests\Frame\BadSubclassFrame';
    }
}
