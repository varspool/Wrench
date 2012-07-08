<?php

namespace Wrench\Exception;

class BadRequestException extends HandshakeException
{
    protected $status = 400;
}