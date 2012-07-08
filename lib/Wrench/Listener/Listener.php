<?php

namespace Wrench\Listener;

interface Listener
{
    public function listen(Server $server);
}