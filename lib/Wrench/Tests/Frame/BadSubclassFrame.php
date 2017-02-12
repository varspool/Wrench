<?php

namespace Wrench\Tests\Frame;

use Wrench\Frame\HybiFrame;

class BadSubclassFrame extends HybiFrame
{
    protected $payload = 'asdmlasdkm';
    protected $buffer = false;
}
