<?php

namespace WebSocket\Exception;

class BadRequestException extends HandshakeException
{
    protected $status = 400;
}