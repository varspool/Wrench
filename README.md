<!-- vim: set tw=79 sw=4 ts=4 et ft=markdown : -->
# WebSocket
## Simple WebSocket server for PHP

Version: **2.0.0-beta**

A simple websocket server for PHP 5.3, using streams.

### Features

- Supports websocket draft hybi-10,13 (Currently tested with Chrome 18 and
  Firefox 11).
- Supports origin-check.
- Supports various security/performance settings.
- Supports binary frames. (Currently receive only)
- Supports wss. (Needs valid certificate in Firefox.)

### Backward compatibility

The public API of the server should remain compatible with early versions of
php-websocket. The WebSocket namespace begins in the `/lib` directory. The
client class has been integrated into this namespace too.

#### What happened to the `client` dir?

The client-side libraries are no longer supported: some libraries are included
but are packaged only as examples. You're free to use whatever client-side
libraries you'd like with the server. If you're still using them, see the 1.0
branch.

## Installation

The library is PSR-0 compatible, with a vendor name of WebSocket (note the
capital S). An SplClassLoader is bundled for convenience.

## Usage

This creates a server on 127.0.0.1:8000 with one Application that listens on
`ws://localhost:8000/demo`:

```php
// $interface, $port, $ssl
$server = new \WebSocket\Server(127.0.0.1', 8000, false);

// Origin checking is supported
$server->setCheckOrigin(true);
$server->setAllowedOrigin('example.org')

// As is basic rate limiting
$server->setMaxClients(100);
$server->setMaxConnectionsPerIp(20);
$server->setMaxRequestsPerMinute(1000);

$server->registerApplication('demo', \WebSocket\Application\DemoApplication::getInstance());
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

- Add support for fragmented frames.
- To report issues, see the [issue tracker](https://github.com/varspool/php-websocket/issues).

## Examples

- [Jitt.li](http://jitt.li), a Twitter API sample project.
- For Symfony2, [VarspoolWebsocketBundle](https://github.com/varspool/WebsocketBundle)
  extends this library for use with the Service Container.
