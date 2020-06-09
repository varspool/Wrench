<?php

namespace Wrench\Tests\Frame;

use Wrench\Tests\Test;

class BadSubclassFrameTest extends Test
{
    public function testInvalidFrameBuffer()
    {
        $this->expectException(\Wrench\Exception\FrameException::class);

        $frame = new BadSubclassFrame();
        $frame->getFrameBuffer();
    }

    protected function getClass()
    {
        return 'Wrench\Tests\Frame\BadSubclassFrame';
    }
}
