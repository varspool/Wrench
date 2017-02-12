<?php

namespace Wrench\Application;

/**
 * @deprecated Rather than extending this class, just implement one or more of these optional interfaces:
 *              - Wrench\Application\DataHandlerInterface for onData()
 *              - Wrench\Application\ConnectionHandlerInterface for onConnect() and onDisconnect()
 *              - Wrench\Application\UpdateHandlerInterface for onUpdate()
 */
abstract class Application implements DataHandlerInterface
{
}
