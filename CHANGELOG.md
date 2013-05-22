<!-- vim: set tw=79 sw=4 ts=4 et ft=markdown : -->
# Changelog

* Allowed access to the headers and query params included in the original
  web sockets upgrade HTTP request

## 2.0.0

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
