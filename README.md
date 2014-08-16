<!-- vim: set tw=79 sw=4 ts=4 et ft=markdown : -->
# Wrench
## Simple WebSocket Client/Server for PHP

* [![Latest Stable  Version](https://poser.pugx.org/wrench/wrench/v/stable.svg)](https://packagist.org/packages/wrench/wrench)
* [![Build Status](https://secure.travis-ci.org/varspool/Wrench.png?branch=master)](http://travis-ci.org/varspool/Wrench)
* Documentation: [wrench.readthedocs.org](http://wrench.readthedocs.org/en/latest/index.html)

A simple websocket server and client package for PHP 5.3/5.4, using
streams. Protocol support is based around [RFC 6455](http://tools.ietf.org/html/rfc6455),
targeting the latest stable versions of Chrome and Firefox.
(Suggest other clients [here](https://github.com/varspool/Wrench/wiki))

### Backward compatibility

#### Public API

The new vendor namespace is Wrench. This namespace begins in the `/lib`
directory, rather than `server/lib`.

Apart from the new namespace, the public API of this new major version is
fairly compatible with that of php-websocket 1.0.0.

#### Protected API

The protected API has changed, a lot. Many methods have been broken up into
simple protected methods. This makes the Server class much easier to extend. In
fact, almost all of the classes involved in your typical daemon can now be
replaced or extended, including the socket and protocol handling.

#### What happened to the `client` dir?

The client-side libraries are no longer supported: some libraries are included
but are packaged only as examples. You're free to use whatever client-side
libraries you'd like with the server.

## Installation

The library is PSR-0 compatible, with a vendor name of **Wrench**. An
SplClassLoader is bundled for convenience.

## Usage

This creates a server on 127.0.0.1:8000 with one Application that listens for
WebSocket requests to `ws://localhost:8000/echo` and `ws://localhost:8000/chat`:

```php
$server = new \Wrench\BasicServer('ws://localhost:8000', array(
    'allowed_origins' => array(
        'mysite.com',
        'mysite.dev.localdomain'
    )
));
$server->registerApplication('echo', new \Wrench\Examples\EchoApplication());
$server->registerApplication('chat', new \My\ChatApplication());
$server->run();
```
## Authors

The original maintainer and author was
[@nicokaiser](https://github.com/nicokaiser). Plentiful improvements were
contributed by [@lemmingzshadow](https://github.com/lemmingzshadow) and
[@mazhack](https://github.com/mazhack). Parts of the Socket class were written
by Moritz Wutz. The server is licensed under the WTFPL, a free software compatible
license.

## Bugs/Todos/Hints

- Add tests around fragmented payloads (split into many frames).
- To report issues, see the [issue tracker](https://github.com/varspool/Wrench/issues).

## Examples

- See server.php in the examples directory and
  Wrench\Application\EchoApplication
- For Symfony2, [VarspoolWebsocketBundle](https://github.com/varspool/WebsocketBundle)
  extends this library for use with the Service Container.

## See Also

- [Ratchet](https://github.com/cboden/Ratchet) an excellent Websocket layer for
  React.
