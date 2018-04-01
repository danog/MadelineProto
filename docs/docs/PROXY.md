# Using a proxy

You can use a proxy with MadelineProto.

There are two ways to do this: either buy a pre-made Socks5 or HTTP proxy for 10$, or build your own proxy.


## Buying a proxy class

Just send 10$ to paypal.me/danog, specifying the the proxy you wish to receive and your telegram username.


## Building a proxy class

```php
class MyProxy implements \danog\MadelineProto\Proxy
{
    //...
}
$MadelineProto->settings['connection_settings']['all']['proxy'] = '\MyProxy';
```

Simply create a class that implements the `\danog\MadelineProto\Proxy` interface, and enter its name in the settings.

Your proxy class MUST use the `\Socket` class for all TCP/UDP communications.

Your proxy class can also have a setExtra method that accepts an array as the first parameter, to pass the values provided in the proxy_extra setting.

The `\Socket` class has the following methods (all of the following methods must also be implemented by your proxy class):


`public function __construct(int $domain, int $type, int $protocol);`

Works exactly like the [socket_connect](http://php.net/manual/en/function.socket-connect.php) function.



`public function setOption(int $level, int $name, $value);`

Works exactly like the [socket_set_option](http://php.net/manual/en/function.socket-set-option.php) function.



`public function getOption(int $name, $value);`

Works exactly like the [socket_get_option](http://php.net/manual/en/function.socket-get-option.php) function.



`public function setBlocking(bool $blocking);`

Works like the [socket_block](http://php.net/manual/en/function.socket-set-block.php) or [socket_nonblock](http://php.net/manual/en/function.socket-set-nonblock.php) functions.



`public function bind(string $address, [ int $port = 0 ]);`

Works exactly like the [socket_bind](http://php.net/manual/en/function.socket-bind.php) function.



`public function listen([ int $backlog = 0 ]);`

Works exactly like the [socket_listen](http://php.net/manual/en/function.socket-listen.php) function.



`public function accept();`

Works exactly like the [socket_accept](http://php.net/manual/en/function.socket-accept.php) function.



`public function connect(string $address, [ int $port = 0 ]);`

Works exactly like the [socket_accept](http://php.net/manual/en/function.socket-connect.php) function.




`public function read(int $length, [ int $flags = 0 ]);`

Works exactly like the [socket_read](http://php.net/manual/en/function.socket-read.php) function.



`public function write(string $buffer, [ int $length ]);`

Works exactly like the [socket_write](http://php.net/manual/en/function.socket-write.php) function.



`public function send(string $data, int $length, int $flags);`

Works exactly like the [socket_send](http://php.net/manual/en/function.socket-send.php) function.



`public function close();`

Works exactly like the [socket_close](http://php.net/manual/en/function.socket-close.php) function.


`public function getPeerName(bool $port = true);`

Works like [socket_getpeername](http://php.net/manual/en/function.socket-getpeername.php): the difference is that it returns an array with the `host` and the `port`.


`public function getSockName(bool $port = true);`

Works like [socket_getsockname](http://php.net/manual/en/function.socket-getsockname.php): the difference is that it returns an array with the `host` and the `port`.


`public function getProxyHeaders();`

Can return additional HTTP headers to use when the HTTP protocol is being used.

`public function getResource();`

Returns the resource used for socket communication: should call `$socket->getResource()`.  

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/LUA.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/CONTRIB.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>