<?php

namespace Wrench\Frame;

use Wrench\Test\BaseTest;

class BadSubclassFrameTest extends BaseTest
{
    /**
     * @expectedException \Wrench\Exception\FrameException
     */
    public function testInvalidFrameBuffer()
    {
        $frame = new BadSubclassFrame();
        $frame->getFrameBuffer();
    }

    protected function getClass()
    {
        return 'Wrench\Test\Frame\BadSubclassFrame';
    }
}
