<!-- vim: set tw=79 sw=4 ts=4 et ft=markdown : -->
# Changelog

## 3.0

### 3.0.0 (UNRELEASED)

* Dropped support for PHP versions prior to 7.1
* Moved to PSR4 layout, with a separate test directory (this means test code cannot be autoloaded in production, much nicer)
* Added support for PSR3 everywhere that we used to accept a logging callback (just inject via LoggerAwareInterface)

#### BC Breaks

* We no longer support the $config['logger'] option on many objects, including the `Server`. Instead,
  you should use the PSR3 support that has been added: call `$server->setLogger($logger)` with a LoggerInterface.
  Or inject any PSR3 compatible logger (e.g. `Monolog\Logger`)
* The Connection class's `id` is now random, and not a consistent hash of information about the
  connection. This has implications for the way the rate limiter works. But is much nicer in the long term.

#### Deprecations

* Extending the `Wrench\Application\Application` abstract class is now discouraged. It has been replaced
  with three simple interfaces, all of which are optional to implement:
  
     - `Wrench\Application\DataHandlerInterface` for `onData()`
     - `Wrench\Application\ConnectionHandlerInterface` for `onConnect()` and `onDisconnect()`
     - `Wrench\Application\UpdateHandlerInterface` for `onUpdate()`
   
The Application class is still available, and so this will not be a BC break until version 4.

## 2.0

### 2.0.8

* Allowed access to the headers and query params included in the original
  web sockets upgrade HTTP request
* Bugfixes for socket options, error messages, json encoding, and more by @nexen2,
  @Alarmfifa, @guweigang, @joy2fun, @emadruida, and others.
* Added PHPCS rules
* Added testing under PHP7

### 2.0.7

* @DaSpors fixes for getReceivingFrame

### 2.0.0

* Name change: php-websocket was renamed to Wrench, along with a top-level
  namespace change.
* Moved to a more traditional project layout.
* Added composer.json: wrench/wrench is the new package name.
* Added PHPUnit tests, and Travis CI integration
* Everything is now much nicer to override and customize.
* Extensive changes to the protected API, not much change to the public API
  * Deprecated: `$server->removeClientOnClose($client)`,
    `$server->removeClientOnError($client)` (both cases should be managed by
    overriding the server, or hooking into `$client->onDisconnect()`)
  * Deprecated: `StatusApplication` and `DemoApplication`, both moved to
    examples directory.
* Split out new classes (and in some cases hierarchies) for protocol, payload
  frame, connection and event handling.
* Added dependency injection patterns everywhere to split logic out into
  loosely coupled, replacable aggregate classes.
* Added the Configurable interface, providing a way to configure most of the
  primary classes in detail (if you don't feel like extending them).
* Refactored the client class to be in the same namespace as the server
  libraries.
* @vincentdieltiens worked on SSL configuration, and added a method to generate
  a certificate file.

## 1.0.0

* Refactored methods to open up more of the protected API.
* @lemmingzshadow switched the server to use streams instead of sockets, and
  implemented SSL support.
* @mazhack added support for the new WebSocket object in Firefox 11.
* Plenty of bugfixes
