# TON integration

MadelineProto is now capable of integrating with the [Telegram TON blockchain](https://test.ton.org), thanks to a fully native implementation of ADNL and the lite-client protocol.

It allows **async** interaction with liteservers in the same manner as the official `lite-client`, only with way more abstractions and ease of use.

Please note that the project is in alpha stage.


For a **fully separate and standalone** pure JS client-side implementation of the TON protocol check out [madelineTon.js](https://github.com/danog/madelineTon.js): interact **directly with the TON blockchain** with no middlemans, directly from your browser!


## Instantiation

```php
use danog\MadelineProto\TON\API;

$API = new API(
    [
        'logger' => [
            'logger' => Logger::ECHO_LOGGER
        ]
    ]
);
```

## Usage

```php
$API->async(true);
$API->loop(
    function () use ($API) {
        yield $API->connect(__DIR__.'/ton-lite-client-test1.config.json');
        var_dump(yield $API->liteServer->getTime());
    }
);
```

For a full overview of async in MadelineProtoTon, take a look at the [MadelineProto async docs](https://docs.madelineproto.xyz/docs/ASYNC.html).

For a full list of methods that can be used, simply look at the PHPDOC suggestions in your favorite IDE, or take a look at the `---functions---` section of the [lite TL scheme](https://github.com/danog/MadelineProto/blob/master/src/danog/MadelineProto/TON/schemes/lite_api.tl).

This API can be used to build web-facing HTTP APIs that query the TON blockchain (using the async [http-client](https://github.com/amphp/http-client)), off-chain elements for TON applications and so much more!


For a **fully separate and standalone** pure JS client-side implementation of the TON protocol check out [madelineTon.js](https://github.com/danog/madelineTon.js): interact **directly with the TON blockchain** with no middlemans, directly from your browser!
