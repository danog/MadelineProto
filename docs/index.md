# MadelineProto, a PHP MTProto telegram client

Do join the official channel, [@MadelineProto](https://t.me/MadelineProto)!


## What's this?

This library can be used to easily interact with Telegram without the bot API, just like the official apps.


## Installation

Simply download [madeline.php](https://phar.madelineproto.xyz/madeline.php).

## Getting started

```
<?php

include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash']]);

$MadelineProto->phone_login(readline('Enter your phone number: '));
$authorization = $MadelineProto->complete_phone_login(readline('Enter the phone code: '));
if ($authorization['_'] === 'account.password') {
    $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
}
if ($authorization['_'] === 'account.needSignup') {
    $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
}
    
```

## Simple example

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => "Hi!\nThanks for creating MadelineProto! <3"]);
$MadelineProto->channels->joinChannel(['channel' => '@MadelineProto']);
```

## Documentation

- [Features](docs.madelineproto.xyz/#features)
- [Methods](docs.madelineproto.xyz/#methods)
- [Clicking inline buttons](docs.madelineproto.xyz/#inline-buttons)
- [Uploading and downloading files](docs.madelineproto.xyz/#uploading-and-downloading-files)
- [Changing settings](docs.madelineproto.xyz/#settings)
- [Update management (getting incoming messages)](docs.madelineproto.xyz/#handling-updates)
- [Using a proxy](docs.madelineproto.xyz/#using-a-proxy)
- [Calls](docs.madelineproto.xyz/#calls)
- [Secret chats](docs.madelineproto.xyz/#secret-chats)
- [Storing sessions](docs.madelineproto.xyz/#storing-sessions)
- [Exceptions](docs.madelineproto.xyz/#exceptions)
- [Lua binding](docs.madelineproto.xyz/#lua-binding)


## Very complex and complete examples

You can find examples for nearly every MadelineProto function in
* [`tests/testing.php`](https://github.com/danog/MadelineProto/blob/master/tests/testing.php) - examples for making/receiving calls, making secret chats, sending secret chat messages, videos, audios, voice recordings, gifs, stickers, photos, sending normal messages, videos, audios, voice recordings, gifs, stickers, photos.
* [`bot.php`](https://github.com/danog/MadelineProto/blob/master/bot.php) - examples for sending normal messages, downloading any media
* [`secret_bot.php`](https://github.com/danog/MadelineProto/blob/master/secret_bot.php) - secret chat bot
* [`multiprocess_bot.php`](https://github.com/danog/MadelineProto/blob/master/multiprocess_bot.php) - multithreaded bot
* [`magna.php`](https://github.com/danog/MadelineProto/blob/master/magna.php) - examples for receiving calls
* [`userbots/pipesbot.php`](https://github.com/danog/MadelineProto/blob/master/userbots/pipesbot.php) - examples for creating inline bots and using other inline bots via a userbot
* [`userbots/MadelineProto_bot.php`](https://github.com/danog/MadelineProto/blob/master/userbots/MadelineProto_bot.php) - Multi-function bot
* [`userbots/pwrtelegram_debug_bot`](https://github.com/danog/MadelineProto/blob/master/userbots/pwrtelegram_debug_bot.php) - Multi-function bot


