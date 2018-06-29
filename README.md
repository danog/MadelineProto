# MadelineProto, a PHP MTProto telegram client

Do join the official channel, [@MadelineProto](https://t.me/MadelineProto) and the [support groups](https://t.me/pwrtelegramgroup)!


## What's this?

This library can be used to easily interact with Telegram **without** the bot API, just like the official apps.

It can login with a phone number (MTProto API), or with a bot token (MTProto API, **no bot API involved!**).

## Getting started

```php
<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$me = $MadelineProto->get_self();

\danog\MadelineProto\Logger::log($me);

if (!$me['bot']) {
    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => "Hi!\nThanks for creating MadelineProto! <3"]);
    $MadelineProto->channels->joinChannel(['channel' => '@MadelineProto']);

    try {
        $MadelineProto->messages->importChatInvite(['hash' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg']);
    } catch (\danog\MadelineProto\RPCErrorException $e) {
    }

    $MadelineProto->messages->sendMessage(['peer' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg', 'message' => 'Testing MadelineProto!']);
}
echo 'OK, done!'.PHP_EOL;
```

[Try this code now!](https://try.madelineproto.xyz) or run this code in a browser or in a console. 


Tip: if you receive an error (or nothing), [send us](https://t.me/pwrtelegramgroup) the error message and the `MadelineProto.log` file that was created in the same directory (if running from a browser).  

## Documentation

* [Creating a client](https://docs.madelineproto.xyz/docs/CREATING_A_CLIENT.html)
* [Logging in](https://docs.madelineproto.xyz/docs/LOGIN.html)
  * [Automatic](https://docs.madelineproto.xyz/docs/LOGIN.html#automatic)
  * [Manual (user)](https://docs.madelineproto.xyz/docs/LOGIN.html#manual-user)
  * [Manual (bot)](https://docs.madelineproto.xyz/docs/LOGIN.html#manual-bot)
  * [Logout](https://docs.madelineproto.xyz/docs/LOGIN.html#logout)
* [Features](https://docs.madelineproto.xyz/docs/FEATURES.html)
* [Requirements](https://docs.madelineproto.xyz/docs/REQUIREMENTS.html)
* [Installation](https://docs.madelineproto.xyz/docs/INSTALLATION.html)
  * [Simple](https://docs.madelineproto.xyz/docs/INSTALLATION.html#simple)
  * [Simple (manual)](https://docs.madelineproto.xyz/docs/INSTALLATION.html#simple-manual)
  * [Composer from scratch](https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-scratch)
  * [Composer from existing project](https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-existing-project)
* [Handling updates](https://docs.madelineproto.xyz/docs/UPDATES.html)
  * [Event driven](https://docs.madelineproto.xyz/docs/UPDATES.html#event-driven)
    * [Event driven multithreaded](https://docs.madelineproto.xyz/docs/UPDATES.html#event-driven-multithreaded)
  * [Webhook](https://docs.madelineproto.xyz/docs/UPDATES.html#webhook)
    * [Webhook multithreaded](https://docs.madelineproto.xyz/docs/UPDATES.html#webhook-multithreaded)
  * [Long polling (getupdates)](https://docs.madelineproto.xyz/docs/UPDATES.html#long-polling)
  * [Callback](https://docs.madelineproto.xyz/docs/UPDATES.html#callback)
    * [Callback multithreaded](https://docs.madelineproto.xyz/docs/UPDATES.html#callback-multithreaded)
* [Settings](https://docs.madelineproto.xyz/docs/SETTINGS.html)
* [Getting info about the current user](https://docs.madelineproto.xyz/docs/SELF.html)
* [Exceptions](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html)
  * [List of exception types](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#list-of-exception-types)
  * [Pretty TL trace](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#pretty-tl-trace)
  * [Getting the TL trace](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#getting-the-tl-trace)
* [Avoiding FLOOD_WAITs](https://docs.madelineproto.xyz/docs/FLOOD_WAIT.html)
* [Logging](https://docs.madelineproto.xyz/docs/LOGGING.html)
* [Using methods](https://docs.madelineproto.xyz/docs/USING_METHODS.html)
  * [FULL API Documentation with descriptions](https://docs.madelineproto.xyz/API_docs/methods/)
  * [Peers](https://docs.madelineproto.xyz/docs/USING_METHODS.html#peers)
  * [Files](https://docs.madelineproto.xyz/docs/FILES.html)
  * [Secret chats](https://docs.madelineproto.xyz/docs/USING_METHODS.html#secret-chats)
  * [Entities (Markdown & HTML)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#entities)
  * [reply_markup (keyboards & inline keyboards)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#reply_markup)
  * [bot API objects](https://docs.madelineproto.xyz/docs/USING_METHODS.html#bot-api-objects)
  * [No result](https://docs.madelineproto.xyz/docs/USING_METHODS.html#no-result)
  * [Queues](https://docs.madelineproto.xyz/docs/USING_METHODS.html#queues)
* [Uploading and downloading files](https://docs.madelineproto.xyz/docs/FILES.html)
  * [Uploading & sending files](https://docs.madelineproto.xyz/docs/FILES.html#sending-files)
    * [Security notice](https://docs.madelineproto.xyz/docs/FILES.html#security-notice)
    * [Photos](https://docs.madelineproto.xyz/docs/FILES.html#inputmediauploadedphoto)
    * [Documents](https://docs.madelineproto.xyz/docs/FILES.html#inputmediauploadeddocument)
      * [Documents](https://docs.madelineproto.xyz/docs/FILES.html#documentattributefilename-to-send-a-document)
      * [Photos as documents](https://docs.madelineproto.xyz/docs/FILES.html#documentattributeimagesize-to-send-a-photo-as-document)
      * [GIFs](https://docs.madelineproto.xyz/docs/FILES.html#documentattributeanimated-to-send-a-gif)
      * [Videos](https://docs.madelineproto.xyz/docs/FILES.html#documentattributevideo-to-send-a-video)
      * [Audio & Voice](https://docs.madelineproto.xyz/docs/FILES.html#documentattributeaudio-to-send-an-audio-file)
  * [Uploading files](https://docs.madelineproto.xyz/docs/FILES.html#uploading-files)
  * [Bot API file IDs](https://docs.madelineproto.xyz/docs/FILES.html#bot-api-file-ids)
  * [Reusing uploaded files](https://docs.madelineproto.xyz/docs/FILES.html#reusing-uploaded-files)
  * [Downloading files](https://docs.madelineproto.xyz/docs/FILES.html#downloading-files)
    * [Extracting download info](https://docs.madelineproto.xyz/docs/FILES.html#extracting-download-info)
    * [Download to directory](https://docs.madelineproto.xyz/docs/FILES.html#download-to-directory)
    * [Download to file](https://docs.madelineproto.xyz/docs/FILES.html#download-to-file)
    * [Download to browser (streaming)](https://docs.madelineproto.xyz/docs/FILES.html#download-to-browser-with-streams)
  * [Getting progress](https://docs.madelineproto.xyz/docs/FILES.html#getting-progress)
* [Getting info about chats](https://docs.madelineproto.xyz/docs/CHAT_INFO.html)
  * [Full chat info with full list of participants](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_pwr_chat)
  * [Full chat info](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_full_info)
  * [Reduced chat info (very fast)](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_info)
* [Getting all chats (dialogs)](https://docs.madelineproto.xyz/docs/DIALOGS.html)
  * [As user](https://docs.madelineproto.xyz/docs/DIALOGS.html#user-get_dialogs)
  * [As bot](https://docs.madelineproto.xyz/docs/DIALOGS.html#bot-internal-peer-database)
* [Inline buttons](https://docs.madelineproto.xyz/docs/INLINE_BUTTONS.html)
* [Calls](https://docs.madelineproto.xyz/docs/CALLS.html)
  * [Requesting a call](https://docs.madelineproto.xyz/docs/CALLS.html#requesting-a-call)
    * [Playing mp3 files](https://docs.madelineproto.xyz/docs/CALLS.html#playing-mp3-files)
    * [Playing streams](https://docs.madelineproto.xyz/docs/CALLS.html#playing-streams)
    * [Changing audio quality](https://docs.madelineproto.xyz/docs/CALLS.html#changing-audio-quality)
  * [Putting it all together](https://docs.madelineproto.xyz/docs/CALLS.html#putting-it-all-together)
  * [Accepting calls](https://docs.madelineproto.xyz/docs/CALLS.html#accepting-calls)
* [Secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
  * [Requesting secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#requesting-secret-chats)
  * [Accepting secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#accepting-secret-chats)
  * [Checking secret chat status](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#checking-secret-chat-status)
  * [Sending secret messages](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#sending-secret-messages)
* [Lua binding](https://docs.madelineproto.xyz/docs/LUA.html)
* [Using a proxy](https://docs.madelineproto.xyz/docs/PROXY.html)
  * [Use pre-built Socks5 proxy](https://docs.madelineproto.xyz/docs/PROXY.html#socks5-proxy)
  * [Use pre-built HTTP proxy](https://docs.madelineproto.xyz/docs/PROXY.html#http-proxy)
  * [Build your own proxy](https://docs.madelineproto.xyz/docs/PROXY.html#build-your-proxy)
* [Contributing](https://docs.madelineproto.xyz/docs/CONTRIB.html)
  * [Translation](https://docs.madelineproto.xyz/docs/CONTRIB.html#translation)
  * [Contribution guide](https://docs.madelineproto.xyz/docs/CONTRIB.html#contribution-guide)
  * [Credits](https://docs.madelineproto.xyz/docs/CONTRIB.html#credits)
* [Web templates for `$MadelineProto->start()`](https://docs.madelineproto.xyz/docs/TEMPLATES.html)

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


