<!-- vim: set tw=79 sw=4 ts=4 et ft=markdown : -->
# Changelog

## 2.0.0

* Moved to a more traditional project layout
* Refactored the client class to be in the same namespace as the server
  libraries
* Extensive changes to the protected API, not much change to the public API
  * Deprecated: `$server->removeClientOnClose($client)`,
    `$server->removeClientOnError($client)` (both cases should be managed by
    overriding the server, or hooking into `$client->onDisconnect()`)
  * Deprecated: `StatusApplication` and `DemoApplication`, both moved to
    examples directory.
* Added composer.json
* Added PHPUnit tests, and Travis CI integration
* @vincentdieltiens worked on SSL configuration, and added a method to generate
  a certificate file

## 1.0.0

* Refactored methods to open up more of the protected API.
* @lemmingzshadow switched the server to use streams instead of sockets, and
  implemented SSL support.
* @mazhack added support for the new WebSocket object in Firefox 11.
* Plenty of bugfixes
