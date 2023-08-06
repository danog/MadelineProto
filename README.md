# MadelineProto, a PHP MTProto telegram client

Created by <a href="https://daniil.it" target="_blank" rel="noopener">Daniil Gentili</a>

`#StandWithUkraine 🇺🇦`

Do join the official channel, [@MadelineProto](https://t.me/MadelineProto) and the [support groups](https://t.me/pwrtelegramgroup)!

<a href="https://telegram.org/apps" target="_blank" rel="noopener">Approved by Telegram!</a>

## What's this?

This library can be used to easily interact with Telegram **without** the bot API, just like the official apps.

It can login with a phone number (MTProto API), or with a bot token (MTProto API, **no bot API involved!**).

[It is now fully async](https://docs.madelineproto.xyz/docs/ASYNC.html)!

## Getting started ([now fully async!](https://docs.madelineproto.xyz/docs/ASYNC.html))

```php
<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$me = $MadelineProto->getSelf();

$MadelineProto->logger($me);

if (!$me['bot']) {
    $MadelineProto->messages->sendMessage(peer: '@stickeroptimizerbot', message: "/start");

    $MadelineProto->channels->joinChannel(channel: '@MadelineProto');

    try {
        $MadelineProto->messages->importChatInvite(hash: 'https://t.me/+Por5orOjwgccnt2w');
    } catch (\danog\MadelineProto\RPCErrorException $e) {
        $MadelineProto->logger($e);
    }
}
$MadelineProto->echo('OK, done!');
```

Try running this code in a browser or in a console!  


Tip: if you receive an error (or nothing), [send us](https://t.me/pwrtelegramgroup) the error message and the `MadelineProto.log` file that was created in the same directory (if running from a browser).  

## Made with MadelineProto

The following open source projects were created using MadelineProto: you can directly install them, or explore the source code as direct examples on how to use MadelineProto's many features!

<!-- * [magnaluna webradio](https://magna.madelineproto.xyz) - Multifeatured Telegram VoIP webradio -->
* [TelegramApiServer](https://github.com/xtrime-ru/TelegramApiServer) - Fast, simple, async php telegram api server: an HTTP JSON API for MadelineProto!
* [`tgstories_dl_bot.php`](https://github.com/danog/MadelineProto/blob/v8/examples/tgstories_dl_bot.php) - Source code of [@tgstories_dl_bot](https://t.me/tgstories_dl_bot) - Bot to download any Telegram Story!
* [`downloadRenameBot.php`](https://github.com/danog/MadelineProto/blob/v8/examples/downloadRenameBot.php) - Download files by URL and rename Telegram files using this async parallelized bot!
* [`secret_bot.php`](https://github.com/danog/MadelineProto/blob/v8/examples/secret_bot.php) - Secret chat bot!
* [`pipesbot.php`](https://github.com/danog/pipesbot) - Creating inline bots and using other inline bots via a userbot!
* [`bot.php`](https://github.com/danog/MadelineProto/blob/v8/examples/bot.php) - Examples for sending normal messages, downloading any media!

Want to add your own open-source project to this list? [Click here!](https://docs.madelineproto.xyz/FOSS.html)

## Documentation

* [Creating a client](https://docs.madelineproto.xyz/docs/CREATING_A_CLIENT.html)
* [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)
  * [Getting permission to use the telegram API](https://docs.madelineproto.xyz/docs/LOGIN.html#getting-permission-to-use-the-telegram-api)
  * [Automatic](https://docs.madelineproto.xyz/docs/LOGIN.html#automatic)
  * [Manual (user)](https://docs.madelineproto.xyz/docs/LOGIN.html#manual-user)
    * [API ID](https://docs.madelineproto.xyz/docs/LOGIN.html#api-id)
  * [Manual (bot)](https://docs.madelineproto.xyz/docs/LOGIN.html#manual-bot)
  * [QR code login (user)](https://docs.madelineproto.xyz/docs/LOGIN.html#qr-code-user)
* [Features](https://docs.madelineproto.xyz/docs/FEATURES.html)
* [Requirements](https://docs.madelineproto.xyz/docs/REQUIREMENTS.html)
* [MadelineProto on Docker](https://docs.madelineproto.xyz/docs/DOCKER.html)
  * [Getting started](https://docs.madelineproto.xyz/docs/DOCKER.html#getting-started)
    * [CLI bot (recommended)](https://docs.madelineproto.xyz/docs/DOCKER.html#cli-bot-recommended)
    * [Databases on docker](https://docs.madelineproto.xyz/docs/DOCKER.html#databases-on-docker)
    * [Web docker](https://docs.madelineproto.xyz/docs/DOCKER.html#web-docker)
    * [Custom extensions](https://docs.madelineproto.xyz/docs/DOCKER.html#custom-extensions)
* [Installation](https://docs.madelineproto.xyz/docs/INSTALLATION.html)
  * [Simple](https://docs.madelineproto.xyz/docs/INSTALLATION.html#simple)
  * [Composer from existing project](https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-existing-project)
  * [Composer from scratch](https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-scratch)
* [Broadcasting messages to all users](https://docs.madelineproto.xyz/docs/BROADCAST.html)
* [Handling updates (new messages & other events)](https://docs.madelineproto.xyz/docs/UPDATES.html)
  * [Async Event driven](https://docs.madelineproto.xyz/docs/UPDATES.html#async-event-driven)
    * [Full example](https://docs.madelineproto.xyz/docs/UPDATES.html#async-event-driven)
    * [Bound methods](https://docs.madelineproto.xyz/docs/UPDATES.html#bound-methods)
    * [Filters](https://docs.madelineproto.xyz/docs/FILTERS.html)
      * [Simple filters](https://docs.madelineproto.xyz/docs/FILTERS.html#simple-filters)
      * [Attribute filters](https://docs.madelineproto.xyz/docs/FILTERS.html#attribute-filters)
      * [MTProto filters](https://docs.madelineproto.xyz/docs/FILTERS.html#mtproto-filters)
    * [Plugins](https://docs.madelineproto.xyz/docs/PLUGINS.html)
    * [Cron](https://docs.madelineproto.xyz/docs/UPDATES.html#cron)
    * [Persisting data and IPC](https://docs.madelineproto.xyz/docs/UPDATES.html#persisting-data-and-ipc)
    * [Restarting](https://docs.madelineproto.xyz/docs/UPDATES.html#restarting)
    * [Self-restart on webhosts](https://docs.madelineproto.xyz/docs/UPDATES.html#self-restart-on-webhosts)
    * [Multi-account](https://docs.madelineproto.xyz/docs/UPDATES.html#multiaccount)
    * [Automatic static analysis](https://docs.madelineproto.xyz/docs/UPDATES.html#automatic-static-analysis)
    * [Avoiding the use of filesystem functions](https://docs.madelineproto.xyz/docs/UPDATES.html#avoiding-the-use-of-filesystem-functions)
  * [Webhook (for HTTP APIs)](https://docs.madelineproto.xyz/docs/UPDATES.html#webhook)
  * [getUpdates (only for Javascript APIs)](https://docs.madelineproto.xyz/docs/UPDATES.html#getUpdates)
  * [Noop (default)](https://docs.madelineproto.xyz/docs/UPDATES.html#noop)
  * [Simple filters &raquo;](https://docs.madelineproto.xyz/docs/FILTERS.html#simple-filters)
  * [Attribute filters &raquo;](https://docs.madelineproto.xyz/docs/FILTERS.html#attribute-filters)
  * [MTProto filters &raquo;](https://docs.madelineproto.xyz/docs/FILTERS.html#mtproto-filters)
  * [Configuration](https://docs.madelineproto.xyz/docs/UPDATES.html#configuration)
  * [Creating and uploading text files](https://docs.madelineproto.xyz/docs/UPDATES.html#creating-and-uploading-text-files)
  * [Logging](https://docs.madelineproto.xyz/docs/UPDATES.html#logging)
* [Filters](https://docs.madelineproto.xyz/docs/FILTERS.html)
  * [Simple filters](https://docs.madelineproto.xyz/docs/FILTERS.html#simple-filters)
  * [Attribute filters](https://docs.madelineproto.xyz/docs/FILTERS.html#attribute-filters)
    * [Creating custom attribute filters](https://docs.madelineproto.xyz/docs/FILTERS.html#creating-custom-attribute-filters)
  * [MTProto filters](https://docs.madelineproto.xyz/docs/FILTERS.html#mtproto-filters)
* [Plugins](https://docs.madelineproto.xyz/docs/PLUGINS.html)
  * [Installing plugins](https://docs.madelineproto.xyz/docs/PLUGINS.html#installing-plugins)
    * [Simple installation](https://docs.madelineproto.xyz/docs/PLUGINS.html#simple-installation)
    * [Composer installation](https://docs.madelineproto.xyz/docs/PLUGINS.html#composer-installation)
  * [Creating plugins](https://docs.madelineproto.xyz/docs/PLUGINS.html#creating-plugins)
    * [Full plugin example](https://docs.madelineproto.xyz/docs/PLUGINS.html#full-plugin-example)
    * [Limitations](https://docs.madelineproto.xyz/docs/PLUGINS.html#limitations)
    * [Namespace requirements](https://docs.madelineproto.xyz/docs/PLUGINS.html#namespace-requirements)
    * [Distribution](https://docs.madelineproto.xyz/docs/PLUGINS.html#distribution)
  * [Configuration](https://docs.madelineproto.xyz/docs/UPDATES.html#configuration)
  * [Creating and uploading text files](https://docs.madelineproto.xyz/docs/UPDATES.html#creating-and-uploading-text-files)
  * [Logging](https://docs.madelineproto.xyz/docs/UPDATES.html#logging)
* [Database](https://docs.madelineproto.xyz/docs/DATABASE.html)
  * [\danog\MadelineProto\Settings\Database\Memory: Memory backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Memory.html)
  * [\danog\MadelineProto\Settings\Database\Mysql: MySQL backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Mysql.html)
  * [\danog\MadelineProto\Settings\Database\Postgres: Postgres backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Postgres.html)
  * [\danog\MadelineProto\Settings\Database\Redis: Redis backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Redis.html)
* [Settings](https://docs.madelineproto.xyz/docs/SETTINGS.html)
  * [AppInfo: App information.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/AppInfo.html)
  * [Auth: Cryptography settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Auth.html)
  * [Connection: Connection settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Connection.html)
  * [Files: File management settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Files.html)
  * [Logger: Logger settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Logger.html)
  * [Peer: Peer database settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Peer.html)
  * [Pwr: PWRTelegram settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Pwr.html)
  * [RPC: RPC settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/RPC.html)
  * [SecretChats: Secret chat settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/SecretChats.html)
  * [Serialization: Serialization settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Serialization.html)
  * [TLSchema: TL schema settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/TLSchema.html)
  * [Templates: Web and CLI template settings for login.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Templates.html)
  * [VoIP: VoIP settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/VoIP.html)
  * [Database\Memory: Memory backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Memory.html)
  * [Database\Mysql: MySQL backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Mysql.html)
  * [Database\Postgres: Postgres backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Postgres.html)
  * [Database\Redis: Redis backend settings.](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/Database/Redis.html)
* [Getting info about the current user](https://docs.madelineproto.xyz/docs/SELF.html)
* [Exceptions](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html)
  * [List of exception types](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#list-of-exception-types)
  * [Pretty TL trace](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#pretty-tl-trace)
  * [Getting the TL trace](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#getting-the-tl-trace)
* [Avoiding FLOOD_WAITs](https://docs.madelineproto.xyz/docs/FLOOD_WAIT.html)
* [Logging](https://docs.madelineproto.xyz/docs/LOGGING.html)
* [Telegram VoIP phone calls](https://docs.madelineproto.xyz/docs/CALLS.html)
  * [Requesting a call](https://docs.madelineproto.xyz/docs/CALLS.html#requesting-a-call)
    * [Playing mp3 files](https://docs.madelineproto.xyz/docs/CALLS.html#playing-mp3-files)
    * [Playing streams](https://docs.madelineproto.xyz/docs/CALLS.html#playing-streams)
    * [Changing audio quality](https://docs.madelineproto.xyz/docs/CALLS.html#changing-audio-quality)
  * [Putting it all together](https://docs.madelineproto.xyz/docs/CALLS.html#putting-it-all-together)
  * [Accepting calls](https://docs.madelineproto.xyz/docs/CALLS.html#accepting-calls)
* [Uploading and downloading files](https://docs.madelineproto.xyz/docs/FILES.html)
  * [Bot API file IDs](https://docs.madelineproto.xyz/docs/FILES.html#bot-api-file-ids)
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
  * [Reusing uploaded files](https://docs.madelineproto.xyz/docs/FILES.html#reusing-uploaded-files)
  * [Renaming files](https://docs.madelineproto.xyz/docs/FILES.html#renaming-files)
  * [Downloading files](https://docs.madelineproto.xyz/docs/FILES.html#downloading-files)
    * [Extracting download info](https://docs.madelineproto.xyz/docs/FILES.html#extracting-download-info)
    * [Getting a download link](https://docs.madelineproto.xyz/docs/FILES.html#getting-a-download-link)
    * [Downloading profile pictures](https://docs.madelineproto.xyz/docs/FILES.html#downloading-profile-pictures)
    * [Download to directory](https://docs.madelineproto.xyz/docs/FILES.html#download-to-directory)
    * [Download to file](https://docs.madelineproto.xyz/docs/FILES.html#download-to-file)
    * [Download to stream](https://docs.madelineproto.xyz/docs/FILES.html#download-to-stream)
    * [Download to callback](https://docs.madelineproto.xyz/docs/FILES.html#download-to-callback)
    * [Download to http-server](https://docs.madelineproto.xyz/docs/FILES.html#download-to-http-server)
    * [Download to browser](https://docs.madelineproto.xyz/docs/FILES.html#download-to-browser)
  * [Getting progress](https://docs.madelineproto.xyz/docs/FILES.html#getting-progress)
* [Getting info about chats](https://docs.madelineproto.xyz/docs/CHAT_INFO.html)
  * [Full chat info with full list of participants](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#getPwrChat)
  * [Full chat info](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#getFullInfo)
  * [Reduced chat info (very fast)](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#getInfo)
  * [Just the chat ID (extremely fast)](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#getId)
* [Getting all chats (dialogs)](https://docs.madelineproto.xyz/docs/DIALOGS.html)
  * [Dialog ID list](https://docs.madelineproto.xyz/docs/DIALOGS.html#getDialogIds)
  * [Dialog list](https://docs.madelineproto.xyz/docs/DIALOGS.html#getDialogs)
  * [Full dialog info](https://docs.madelineproto.xyz/docs/DIALOGS.html#getFullDialogs)
* [Inline buttons](https://docs.madelineproto.xyz/docs/INLINE_BUTTONS.html)
* [Secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
  * [Requesting secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#requesting-secret-chats)
  * [Accepting secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#accepting-secret-chats)
  * [Checking secret chat status](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#checking-secret-chat-status)
  * [Sending secret messages](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#sending-secret-messages)
* [Using a proxy](https://docs.madelineproto.xyz/docs/PROXY.html)
  * [How to set a proxy](https://docs.madelineproto.xyz/docs/PROXY.html#how-to-set-a-proxy)
  * [Multiple proxies with automatic switch](https://docs.madelineproto.xyz/docs/PROXY.html#multiple-proxies-with-automatic-switch)
  * [Use pre-built MTProxy](https://docs.madelineproto.xyz/docs/PROXY.html#mtproxy)
  * [Use pre-built Socks5 proxy](https://docs.madelineproto.xyz/docs/PROXY.html#socks5-proxy)
  * [Use pre-built HTTP proxy](https://docs.madelineproto.xyz/docs/PROXY.html#http-proxy)
  * [Build your own proxy](https://docs.madelineproto.xyz/docs/PROXY.html#build-your-proxy)
* [Async](https://docs.madelineproto.xyz/docs/ASYNC.html)
  * [Usage](https://docs.madelineproto.xyz/docs/ASYNC.html#usage)
    * [Async in event handler](https://docs.madelineproto.xyz/docs/ASYNC.html#async-in-event-handler)
    * [Multiple async](https://docs.madelineproto.xyz/docs/ASYNC.html#multiple-async)
    * [Ignored async](https://docs.madelineproto.xyz/docs/ASYNC.html#ignored-async)
    * [Combining async operations](https://docs.madelineproto.xyz/docs/ASYNC.html#combining-async-operations)
    * [Blocking async](https://docs.madelineproto.xyz/docs/ASYNC.html#blocking-async)
    * [MadelineProto and AMPHP async APIs](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-and-amphp-async-apis)
      * [Helper methods](https://docs.madelineproto.xyz/docs/ASYNC.html#helper-methods)
        * [Async sleep](https://docs.madelineproto.xyz/docs/ASYNC.html#async-sleep-does-not-block-the-main-thread)
        * [Async readline](https://docs.madelineproto.xyz/docs/ASYNC.html#async-readline-does-not-block-the-main-thread)
        * [Async echo](https://docs.madelineproto.xyz/docs/ASYNC.html#async-echo-does-not-block-the-main-thread)
        * [MadelineProto HTTP client](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-http-client)
        * [Async forking](https://docs.madelineproto.xyz/docs/ASYNC.html#async-forking-does-async-green-thread-forking)
        * [Async flock](https://docs.madelineproto.xyz/docs/ASYNC.html#async-flock)
      * [MadelineProto async loop APIs](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis)
* [FAQ](https://docs.madelineproto.xyz/docs/FAQ.html)
* [Using methods](https://docs.madelineproto.xyz/docs/USING_METHODS.html)
  * [Named arguments (PHP 8+)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#named-arguments)
  * [Peers](https://docs.madelineproto.xyz/docs/USING_METHODS.html#peers)
  * [Files](https://docs.madelineproto.xyz/docs/FILES.html)
  * [Secret chats](https://docs.madelineproto.xyz/docs/USING_METHODS.html#secret-chats)
  * [Entities (Markdown & HTML)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#entities)
  * [reply_markup (keyboards & inline keyboards)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#reply_markup)
  * [bot API objects](https://docs.madelineproto.xyz/docs/USING_METHODS.html#bot-api-objects)
  * [No result](https://docs.madelineproto.xyz/docs/USING_METHODS.html#no-result)
  * [Queues](https://docs.madelineproto.xyz/docs/USING_METHODS.html#queues)
  * [Multiple method calls](https://docs.madelineproto.xyz/docs/USING_METHODS.html#multiple-method-calls)
  * [FULL API Documentation with descriptions](https://docs.madelineproto.xyz/API_docs/methods/)
    * [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)
    * [Change 2FA password: update2FA](https://docs.madelineproto.xyz/update2fa.html)
    * [Get all chats, broadcast a message to all chats: getDialogIds, getDialogs, getFullDialogs](https://docs.madelineproto.xyz/docs/DIALOGS.html)
    * [Get the full participant list of a channel/group/supergroup: getPwrChat](https://docs.madelineproto.xyz/getPwrChat.html)
    * [Get full info about a user/chat/supergroup/channel: getFullInfo](https://docs.madelineproto.xyz/getFullInfo.html)
    * [Get info about a user/chat/supergroup/channel: getInfo](https://docs.madelineproto.xyz/getInfo.html)
    * [Get the ID of a user/chat/supergroup/channel/update: getID](https://docs.madelineproto.xyz/getId.html)
    * [Get info about the currently logged-in user: getSelf](https://docs.madelineproto.xyz/getSelf.html)
    * [Upload or download files up to 4 GB: uploadFrom*, downloadTo*](https://docs.madelineproto.xyz/docs/FILES.html)
    * [Make a phone call and play a song](https://docs.madelineproto.xyz/docs/CALLS.html)
    * [Create a secret chat bot](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.declinePasswordReset.html" name="account.declinePasswordReset">Abort a pending 2FA password reset, see here for more info »: account.declinePasswordReset</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.acceptLoginToken.html" name="auth.acceptLoginToken">Accept QR code login token, logging in the app that generated it: auth.acceptLoginToken</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#acceptcall-array-call-bool" name="acceptCall">Accept call: acceptCall</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#acceptsecretchat-array-params-void" name="acceptSecretChat">Accept secret chat: acceptSecretChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.acceptTermsOfService.html" name="help.acceptTermsOfService">Accept the new terms of service: help.acceptTermsOfService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.toggleUsername.html" name="bots.toggleUsername">Activate or deactivate a purchased fragment.com username associated to a bot we own: bots.toggleUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleUsername.html" name="channels.toggleUsername">Activate or deactivate a purchased fragment.com username associated to a supergroup or channel we own: channels.toggleUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.toggleUsername.html" name="account.toggleUsername">Activate or deactivate a purchased fragment.com username associated to the currently logged-in user: account.toggleUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveGif.html" name="messages.saveGif">Add GIF to saved gifs list: messages.saveGif</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.addStickerToSet.html" name="stickers.addStickerToSet">Add a sticker to a stickerset, bots only. The sticker set must have been created by the bot: stickers.addStickerToSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.addContact.html" name="contacts.addContact">Add an existing telegram user as contact: contacts.addContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveRecentSticker.html" name="messages.saveRecentSticker">Add/remove sticker from recent stickers list: messages.saveRecentSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.addChatUser.html" name="messages.addChatUser">Adds a user to a chat and sends a service message on it: messages.addChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.block.html" name="contacts.block">Adds the user to the blacklist: contacts.block</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setInlineBotResults.html" name="messages.setInlineBotResults">Answer an inline query, for bots only: messages.setInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.answerWebhookJSONQuery.html" name="bots.answerWebhookJSONQuery">Answers a custom query; for bots only: bots.answerWebhookJSONQuery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.toggleStickerSets.html" name="messages.toggleStickerSets">Apply changes to multiple stickersets: messages.toggleStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.setDiscussionGroup.html" name="channels.setDiscussionGroup">Associate a group to a channel as discussion group for that channel: channels.setDiscussionGroup</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.setStickers.html" name="channels.setStickers">Associate a stickerset to the supergroup: channels.setStickers</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#flock-string-file-int-operation-float-polling-0-1-amp-cancellation-token-null-closure-failurecb-null-mixed" name="flock">Asynchronously lock a file: flock</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#readline-string-prompt-amp-cancellation-cancel-null-string" name="readLine">Asynchronously read line: readLine</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sleep-float-time-void" name="sleep">Asynchronously sleep: sleep</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#echo-string-string-void" name="echo">Asynchronously write to stdout/browser: echo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editBanned.html" name="channels.editBanned">Ban/unban/kick a user in a supergroup/channel: channels.editBanned</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#base64urlencode-string-data-string" name="base64urlEncode">Base64URL encode: base64urlEncode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getUserInfo.html" name="help.getUserInfo">Can only be used by TSF members to obtain internal information: help.getUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#cancelbroadcast-int-id-void" name="cancelBroadcast">Cancel a running broadcast: cancelBroadcast</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.cancelPasswordEmail.html" name="account.cancelPasswordEmail">Cancel the code that was sent to verify an email to use as 2FA recovery method: account.cancelPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.cancelCode.html" name="auth.cancelCode">Cancel the login verification code: auth.cancelCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.changeAuthorizationSettings.html" name="account.changeAuthorizationSettings">Change authorization settings: account.changeAuthorizationSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setDefaultReaction.html" name="messages.setDefaultReaction">Change default emoji reaction to use in the quick reaction menu: the value is synced across devices and can be fetched using help.getConfig, reactions_default field: messages.setDefaultReaction</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.toggleGroupCallSettings.html" name="phone.toggleGroupCallSettings">Change group call settings: phone.toggleGroupCallSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveAutoDownloadSettings.html" name="account.saveAutoDownloadSettings">Change media autodownload settings: account.saveAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.updateUsername.html" name="channels.updateUsername">Change or remove the username of a supergroup/channel: channels.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setPrivacy.html" name="account.setPrivacy">Change privacy settings of current account: account.setPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setChatTheme.html" name="messages.setChatTheme">Change the chat theme of a certain chat: messages.setChatTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveDefaultSendAs.html" name="messages.saveDefaultSendAs">Change the default peer that should be used when sending messages to a specific group: messages.saveDefaultSendAs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.changePhone.html" name="account.changePhone">Change the phone number of the current account: account.changePhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editPhoto.html" name="channels.editPhoto">Change the photo of a channel/supergroup: channels.editPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setChatAvailableReactions.html" name="messages.setChatAvailableReactions">Change the set of message reactions » that can be used in a certain group, supergroup or channel: messages.setChatAvailableReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatTitle.html" name="messages.editChatTitle">Changes chat name and sends a service message on it: messages.editChatTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatPhoto.html" name="messages.editChatPhoto">Changes chat photo and sends a service message on it: messages.editChatPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.changeStickerPosition.html" name="stickers.changeStickerPosition">Changes the absolute position of a sticker in the set to which it belongs; for bots only. The sticker set must have been created by the bot: stickers.changeStickerPosition</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setDefaultHistoryTTL.html" name="messages.setDefaultHistoryTTL">Changes the default value of the Time-To-Live setting, applied to all new chats: messages.setDefaultHistoryTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateUsername.html" name="account.updateUsername">Changes username for the current user: account.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#hasplugin-class-string-eventhandler-class-bool" name="hasPlugin">Check if a certain event handler plugin is installed: hasPlugin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.checkUsername.html" name="channels.checkUsername">Check if a username is free and can be assigned to a channel/supergroup: channels.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#haseventhandler-bool" name="hasEventHandler">Check if an event handler instance is present: hasEventHandler</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#hasadmins-bool" name="hasAdmins">Check if has admins: hasAdmins</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#hasreportpeers-bool" name="hasReportPeers">Check if has report peers: hasReportPeers</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isarrayoralike-mixed-var-bool" name="isArrayOrAlike">Check if is array or similar (traversable && countable && arrayAccess): isArrayOrAlike</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#peerisset-mixed-id-bool" name="peerIsset">Check if peer is present in internal peer database: peerIsset</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.checkRecoveryPassword.html" name="auth.checkRecoveryPassword">Check if the 2FA recovery code sent using auth.requestPasswordRecovery is valid, before passing it to auth.recoverPassword: auth.checkRecoveryPassword</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isforum-mixed-peer-bool" name="isForum">Check if the specified peer is a forum: isForum</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.checkChatInvite.html" name="messages.checkChatInvite">Check the validity of a chat invite link and get basic info about it: messages.checkChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.checkHistoryImportPeer.html" name="messages.checkHistoryImportPeer">Check whether chat history exported from another chat app can be imported into a specific Telegram chat, click here for more info »: messages.checkHistoryImportPeer</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#issupergroup-int-id-bool" name="isSupergroup">Check whether provided bot API ID is a channel or supergroup: isSupergroup</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#hassecretchat-array-int-chat-bool" name="hasSecretChat">Check whether secret chat exists: hasSecretChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.checkShortName.html" name="stickers.checkShortName">Check whether the given short name is available: stickers.checkShortName</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.checkGroupCall.html" name="phone.checkGroupCall">Check whether the group call Server Forwarding Unit is currently receiving the streams with the specified WebRTC source IDs.  : phone.checkGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.canPurchasePremium.html" name="payments.canPurchasePremium">Checks whether Telegram Premium purchase is possible. Must be called before in-store Premium purchase, official apps only: payments.canPurchasePremium</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearAllDrafts.html" name="messages.clearAllDrafts">Clear all drafts: messages.clearAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteAutoSaveExceptions.html" name="account.deleteAutoSaveExceptions">Clear all peer-specific autosave settings: account.deleteAutoSaveExceptions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.resetBotCommands.html" name="bots.resetBotCommands">Clear bot commands for the specified bot scope and language code: bots.resetBotCommands</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearRecentStickers.html" name="messages.clearRecentStickers">Clear recent stickers: messages.clearRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearRecentReactions.html" name="messages.clearRecentReactions">Clear recently used message reactions: messages.clearRecentReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.clearSavedInfo.html" name="payments.clearSavedInfo">Clear saved payment information: payments.clearSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.clearRecentEmojiStatuses.html" name="account.clearRecentEmojiStatuses">Clears list of recently used emoji statuses: account.clearRecentEmojiStatuses</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#closeconnection-string-message-void" name="closeConnection">Close connection with client, connected via web: closeConnection</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#completephonelogin-string-code-mixed" name="completePhoneLogin">Complet user login using login code: completePhoneLogin</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#complete2falogin-string-password-array" name="complete2faLogin">Complete 2FA login: complete2faLogin</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#completecall-array-params-mixed" name="completeCall">Complete call handshake: completeCall</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#completesignup-string-first_name-string-last_name-array" name="completeSignup">Complete signup to Telegram: completeSignup</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.startHistoryImport.html" name="messages.startHistoryImport">Complete the history import process, importing all messages into the chat.  : messages.startHistoryImport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.confirmPhone.html" name="account.confirmPhone">Confirm a phone number to cancel account deletion, for more info click here »: account.confirmPhone</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#confirmcall-array-params-mixed" name="confirmCall">Confirm call: confirmCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.receivedMessages.html" name="messages.receivedMessages">Confirms receipt of messages by a client, cancels PUSH-notification sending: messages.receivedMessages</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#tosupergroup-int-id-int" name="toSupergroup">Convert MTProto channel ID to bot API channel ID: toSupergroup</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#mtprototobotapi-array-data-array" name="MTProtoToBotAPI">Convert MTProto parameters to bot API parameters: MTProtoToBotAPI</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#tdtotdcli-mixed-params-mixed" name="tdToTdcli">Convert TD parameters to tdcli: tdToTdcli</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#tdtomtproto-array-params-array" name="tdToMTProto">Convert TD to MTProto parameters: tdToMTProto</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#entitiestohtml-string-message-list-array-_-string-offset-int-length-int-entities-bool-allowtelegramtags-false-string" name="entitiesToHtml">Convert a message and a set of entities to HTML: entitiesToHtml</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.convertToGigagroup.html" name="channels.convertToGigagroup">Convert a supergroup to a gigagroup, when requested by channel suggestions: channels.convertToGigagroup</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#fromsupergroup-int-id-int" name="fromSupergroup">Convert bot API channel ID to MTProto channel ID: fromSupergroup</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#botapitomtproto-array-arguments-array" name="botAPIToMTProto">Convert bot API parameters to MTProto parameters: botAPIToMTProto</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#packdouble-float-value-string" name="packDouble">Convert double to binary version: packDouble</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#packsignedlong-int-value-string" name="packSignedLong">Convert integer to base256 long: packSignedLong</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#packsignedint-int-value-string" name="packSignedInt">Convert integer to base256 signed int: packSignedInt</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#tdclitotd-mixed-params-array-key-null-array" name="tdcliToTd">Convert tdcli parameters to tdcli: tdcliToTd</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#tocamelcase-string-input-string" name="toCamelCase">Convert to camelCase: toCamelCase</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#tosnakecase-string-input-string" name="toSnakeCase">Convert to snake_case: toSnakeCase</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#packunsignedint-int-value-string" name="packUnsignedInt">Convert value to unsigned base256 int: packUnsignedInt</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#stringtostream-string-str-amp-bytestream-readablebuffer" name="stringToStream">Converts a string into an async amphp stream: stringToStream</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.createForumTopic.html" name="channels.createForumTopic">Create a forum topic; requires manage_topics rights: channels.createForumTopic</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.createGroupCall.html" name="phone.createGroupCall">Create a group call or livestream: phone.createGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.createStickerSet.html" name="stickers.createStickerSet">Create a stickerset, bots only: stickers.createStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.createChannel.html" name="channels.createChannel">Create a supergroup/channel: channels.createChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.createTheme.html" name="account.createTheme">Create a theme: account.createTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadWallPaper.html" name="account.uploadWallPaper">Create and upload a new wallpaper: account.uploadWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#arr-mixed-params-array" name="arr">Create array: arr</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.createChat.html" name="messages.createChat">Creates a new chat: messages.createChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteChannel.html" name="channels.deleteChannel">Delete a channel/supergroup: channels.deleteChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteExportedChatInvite.html" name="messages.deleteExportedChatInvite">Delete a chat invite: messages.deleteExportedChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteChat.html" name="messages.deleteChat">Delete a chat: messages.deleteChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.leaveChatlist.html" name="chatlists.leaveChatlist">Delete a folder imported using a chat folder deep link »: chatlists.leaveChatlist</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.deleteExportedInvite.html" name="chatlists.deleteExportedInvite">Delete a previously created chat folder deep link »: chatlists.deleteExportedInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWallPapers.html" name="account.resetWallPapers">Delete all installed wallpapers, reverting to the default wallpaper set: account.resetWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteParticipantHistory.html" name="channels.deleteParticipantHistory">Delete all messages sent by a specific participant of a given supergroup: channels.deleteParticipantHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteRevokedExportedChatInvites.html" name="messages.deleteRevokedExportedChatInvites">Delete all revoked chat invites: messages.deleteRevokedExportedChatInvites</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.dropTempAuthKeys.html" name="auth.dropTempAuthKeys">Delete all temporary authorization keys except for the ones specified: auth.dropTempAuthKeys</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.deleteByPhones.html" name="contacts.deleteByPhones">Delete contacts by phone number: contacts.deleteByPhones</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteTopicHistory.html" name="channels.deleteTopicHistory">Delete message history of a forum topic: channels.deleteTopicHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteMessages.html" name="channels.deleteMessages">Delete messages in a channel/supergroup: channels.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resetSaved.html" name="contacts.resetSaved">Delete saved contacts: contacts.resetSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteScheduledMessages.html" name="messages.deleteScheduledMessages">Delete scheduled messages: messages.deleteScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteSecureValue.html" name="account.deleteSecureValue">Delete stored Telegram Passport documents, for more info see the passport docs »: account.deleteSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deletePhoneCallHistory.html" name="messages.deletePhoneCallHistory">Delete the entire phone call history: messages.deletePhoneCallHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteHistory.html" name="channels.deleteHistory">Delete the history of a supergroup: channels.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteAccount.html" name="account.deleteAccount">Delete the user's account from the telegram servers: account.deleteAccount</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.unregisterDevice.html" name="account.unregisterDevice">Deletes a device by its token, stops sending PUSH-notifications to it: account.unregisterDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.deleteStickerSet.html" name="stickers.deleteStickerSet">Deletes a stickerset we created, bots only: stickers.deleteStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteChatUser.html" name="messages.deleteChatUser">Deletes a user from a chat and sends a service message on it: messages.deleteChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteHistory.html" name="messages.deleteHistory">Deletes communication history: messages.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteMessages.html" name="messages.deleteMessages">Deletes messages by their identifiers: messages.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.deletePhotos.html" name="photos.deletePhotos">Deletes profile photos. The method returns a list of successfully deleted photo IDs: photos.deletePhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.deleteContacts.html" name="contacts.deleteContacts">Deletes several contacts from the list: contacts.deleteContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.unblock.html" name="contacts.unblock">Deletes the user from the blacklist: contacts.unblock</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deactivateAllUsernames.html" name="channels.deactivateAllUsernames">Disable all purchased usernames of a supergroup or channel: channels.deactivateAllUsernames</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#discardcall-array-call-array-reason-array-rating-bool-need_debug-true-danog-madelineproto-voip" name="discardCall">Discard call: discardCall</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#discardsecretchat-int-chat-void" name="discardSecretChat">Discard secret chat: discardSecretChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.dismissSuggestion.html" name="help.dismissSuggestion">Dismiss a suggestion, see here for more info »: help.dismissSuggestion</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.hideChatlistUpdates.html" name="chatlists.hideChatlistUpdates">Dismiss new pending peers recently added to a chat folder deep link »: chatlists.hideChatlistUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.hideChatJoinRequest.html" name="messages.hideChatJoinRequest">Dismiss or approve a chat join request related to a specific chat or channel: messages.hideChatJoinRequest</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.hideAllChatJoinRequests.html" name="messages.hideAllChatJoinRequests">Dismiss or approve all join requests related to a specific chat or channel: messages.hideAllChatJoinRequests</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadtoresponse-array-string-filecallbackinterface-danog-madelineproto-eventhandler-message-messagemedia-amp-http-server-request-request-callable-cb-null-null-int-size-null-null-string-mime-null-null-string-name-null-amp-http-server-response" name="downloadToResponse">Download file to amphp/http-server response: downloadToResponse</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadtobrowser-array-string-filecallbackinterface-danog-madelineproto-eventhandler-message-messagemedia-null-callable-cb-null-null-int-size-null-null-string-name-null-null-string-mime-null-void" name="downloadToBrowser">Download file to browser: downloadToBrowser</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadtocallable-mixed-messagemedia-callable-filecallbackinterface-callable-callable-cb-null-bool-seekable-true-int-offset-0-int-end-1-int-part_size-null-mixed" name="downloadToCallable">Download file to callable: downloadToCallable</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadtodir-mixed-messagemedia-string-filecallbackinterface-dir-callable-cb-null-mixed" name="downloadToDir">Download file to directory: downloadToDir</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadtostream-mixed-messagemedia-mixed-filecallbackinterface-resource-amp-bytestream-writablestream-stream-callable-cb-null-int-offset-0-int-end-1-mixed" name="downloadToStream">Download file to stream: downloadToStream</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadtofile-mixed-messagemedia-string-filecallbackinterface-file-callable-cb-null-string-false" name="downloadToFile">Download file: downloadToFile</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#downloadserver-string-session-void" name="downloadServer">Downloads a file to the browser using the specified session file: downloadServer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.editExportedInvite.html" name="chatlists.editExportedInvite">Edit a chat folder deep link »: chatlists.editExportedInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editExportedChatInvite.html" name="messages.editExportedChatInvite">Edit an exported chat invite: messages.editExportedChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editInlineBotMessage.html" name="messages.editInlineBotMessage">Edit an inline bot message: messages.editInlineBotMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editForumTopic.html" name="channels.editForumTopic">Edit forum topic; requires manage_topics rights: channels.editForumTopic</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.editGroupCallParticipant.html" name="phone.editGroupCallParticipant">Edit information about a given group call participant: phone.editGroupCallParticipant</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editLocation.html" name="channels.editLocation">Edit location of geogroup: channels.editLocation</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editMessage.html" name="messages.editMessage">Edit message: messages.editMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders.editPeerFolders.html" name="folders.editPeerFolders">Edit peers in peer folder: folders.editPeerFolders</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatDefaultBannedRights.html" name="messages.editChatDefaultBannedRights">Edit the default banned rights of a channel/supergroup/group: messages.editChatDefaultBannedRights</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatAbout.html" name="messages.editChatAbout">Edit the description of a group/supergroup/channel: messages.editChatAbout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editTitle.html" name="channels.editTitle">Edit the name of a channel/supergroup: channels.editTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.editGroupCallTitle.html" name="phone.editGroupCallTitle">Edit the title of a group call or livestream: phone.editGroupCallTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateNotifySettings.html" name="account.updateNotifySettings">Edits notification settings from a given user/group, from all users/all groups: account.updateNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.toggleNoForwards.html" name="messages.toggleNoForwards">Enable or disable content protection on a channel or chat: messages.toggleNoForwards</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleForum.html" name="channels.toggleForum">Enable or disable forum functionality in a supergroup: channels.toggleForum</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleAntiSpam.html" name="channels.toggleAntiSpam">Enable or disable the native antispam system: channels.toggleAntiSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.toggleBotInAttachMenu.html" name="messages.toggleBotInAttachMenu">Enable or disable web bot attachment menu »: messages.toggleBotInAttachMenu</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleSignatures.html" name="channels.toggleSignatures">Enable/disable message signatures in channels: channels.toggleSignatures</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.toggleTopPeers.html" name="contacts.toggleTopPeers">Enable/disable top peers: contacts.toggleTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#markdownurlescape-string-what-string" name="markdownUrlEscape">Escape string for URL: markdownUrlEscape</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#markdowncodeblockescape-string-what-string" name="markdownCodeblockEscape">Escape string for markdown codeblock: markdownCodeblockEscape</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#markdownescape-string-what-string" name="markdownEscape">Escape string for markdown: markdownEscape</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#broadcastcustom-danog-madelineproto-broadcast-action-action-danog-madelineproto-broadcast-filter-filter-null-int" name="broadcastCustom">Executes a custom broadcast action with all peers (users, chats, channels) of the bot: broadcastCustom</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.exportChatlistInvite.html" name="chatlists.exportChatlistInvite">Export a folder », creating a chat folder deep link »: chatlists.exportChatlistInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.exportChatInvite.html" name="messages.exportChatInvite">Export an invite link for a chat: messages.exportChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#exportauthorization-array-0-int-string-1-string" name="exportAuthorization">Export authorization: exportAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#extractupdates-array-updates-array" name="extractUpdates">Extract Update constructors from an Updates constructor: extractUpdates</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#extractmessageid-array-updates-int" name="extractMessageId">Extract a message ID from an Updates constructor: extractMessageId</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#extractmessage-array-updates-array" name="extractMessage">Extract a message constructor from an Updates constructor: extractMessage</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#extractmessageupdate-array-updates-array" name="extractMessageUpdate">Extract an update message constructor from an Updates constructor: extractMessageUpdate</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#extractbotapifile-array-info-array" name="extractBotAPIFile">Extract file info from bot API message: extractBotAPIFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getCustomEmojiDocuments.html" name="messages.getCustomEmojiDocuments">Fetch custom emoji stickers »: messages.getCustomEmojiDocuments</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.getChatlistUpdates.html" name="chatlists.getChatlistUpdates">Fetch new chats associated with an imported chat folder deep link ». Must be invoked at most every chatlistupdateperiod seconds (as per the related client configuration parameter »): chatlists.getChatlistUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getSavedRingtones.html" name="account.getSavedRingtones">Fetch saved notification sounds: account.getSavedRingtones</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessageEditData.html" name="messages.getMessageEditData">Find out if a media message's caption can be edited: messages.getMessageEditData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.finishTakeoutSession.html" name="account.finishTakeoutSession">Finish account takeout session: account.finishTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#callfork-generator-amp-future-callable-callable-mixed-args-amp-future-t" name="callFork">Fork a new green thread and execute the passed function in the background: callFork</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#broadcastforwardmessages-mixed-from_peer-list-int-message_ids-bool-drop_author-false-danog-madelineproto-broadcast-filter-filter-null-bool-pin-false-int" name="broadcastForwardMessages">Forwards a list of messages to all peers (users, chats, channels) of the bot: broadcastForwardMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.forwardMessages.html" name="messages.forwardMessages">Forwards messages by their IDs: messages.forwardMessages</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#genvectorhash-array-longs-string" name="genVectorHash">Generate MTProto vector hash: genVectorHash</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.exportLoginToken.html" name="auth.exportLoginToken">Generate a login token, for login via QR code.  : auth.exportLoginToken</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.exportInvoice.html" name="payments.exportInvoice">Generate an invoice deep link: payments.exportInvoice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.exportContactToken.html" name="contacts.exportContactToken">Generates a temporary profile link for the currently logged-in user: contacts.exportContactToken</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getPromoData.html" name="help.getPromoData">Get MTProxy/Public Service Announcement information: help.getPromoData</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getpsrlogger-psr-log-loggerinterface" name="getPsrLogger">Get PSR logger: getPsrLogger</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getGroupCallStreamRtmpUrl.html" name="phone.getGroupCallStreamRtmpUrl">Get RTMP URL and stream key for RTMP livestreams. Can be used even before creating the actual RTMP livestream with phone.createGroupCall (the rtmp_stream flag must be set): phone.getGroupCallStreamRtmpUrl</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmethodnamespaces-array" name="getMethodNamespaces">Get TL namespaces: getMethodNamespaces</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#gettl-danog-madelineproto-tl-tlinterface" name="getTL">Get TL serializer: getTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getPremiumPromo.html" name="help.getPremiumPromo">Get Telegram Premium promotion information: help.getPremiumPromo</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#mbstrlen-string-text-int" name="mbStrlen">Get Telegram UTF-8 length of string: mbStrlen</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDocumentByHash.html" name="messages.getDocumentByHash">Get a document by its SHA256 hash, mainly used for gifs: messages.getDocumentByHash</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getLeftChannels.html" name="channels.getLeftChannels">Get a list of channels/supergroups we left: channels.getLeftChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getDefaultEmojiStatuses.html" name="account.getDefaultEmojiStatuses">Get a list of default suggested emoji statuses: account.getDefaultEmojiStatuses</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getGroupCallJoinAs.html" name="phone.getGroupCallJoinAs">Get a list of peers that can be used to join a group call, presenting yourself as a specific user/channel: phone.getGroupCallJoinAs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getSponsoredMessages.html" name="channels.getSponsoredMessages">Get a list of sponsored messages: channels.getSponsoredMessages</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getwebmessage-string-message-string" name="getWebMessage">Get a message to show to the user when starting the bot: getWebMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getPaymentForm.html" name="payments.getPaymentForm">Get a payment form: payments.getPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getDefaultGroupPhotoEmojis.html" name="account.getDefaultGroupPhotoEmojis">Get a set of suggested custom emoji stickers that can be used as group picture: account.getDefaultGroupPhotoEmojis</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getDefaultProfilePhotoEmojis.html" name="account.getDefaultProfilePhotoEmojis">Get a set of suggested custom emoji stickers that can be used as profile picture: account.getDefaultProfilePhotoEmojis</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getadminids-array" name="getAdminIds">Get admin IDs (equal to all user report peers): getAdminIds</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getArchivedStickers.html" name="messages.getArchivedStickers">Get all archived stickers: messages.getArchivedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getChatThemes.html" name="account.getChatThemes">Get all available chat themes: account.getChatThemes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getSaved.html" name="contacts.getSaved">Get all contacts: contacts.getSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getGroupsForDiscussion.html" name="channels.getGroupsForDiscussion">Get all groups that can be used as discussion groups: channels.getGroupsForDiscussion</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllStickers.html" name="messages.getAllStickers">Get all installed stickers: messages.getAllStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAllSecureValues.html" name="account.getAllSecureValues">Get all saved Telegram Passport documents, for more info see the passport docs »: account.getAllSecureValues</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.exportGroupCallInvite.html" name="phone.exportGroupCallInvite">Get an invite link for a group call or livestream: phone.exportGroupCallInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessagesViews.html" name="messages.getMessagesViews">Get and increase the view counter of a message sent or forwarded from a channel: messages.getMessagesViews</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppConfig.html" name="help.getAppConfig">Get app-specific configuration, see client configuration for more info on the result: help.getAppConfig</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getdnsclient-amp-dns-dnsresolver" name="getDNSClient">Get async DNS client: getDNSClient</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#gethttpclient-amp-http-client-httpclient" name="getHTTPClient">Get async HTTP client: getHTTPClient</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getauthorization-danog-madelineproto-api-not_logged_in-danog-madelineproto-api-waiting_code-danog-madelineproto-api-waiting_signup-danog-madelineproto-api-waiting_password-danog-madelineproto-api-logged_in-api-logged_out" name="getAuthorization">Get authorization info: getAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAutoSaveSettings.html" name="account.getAutoSaveSettings">Get autosave settings: account.getAutoSaveSettings</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getconfig-array-config-array" name="getConfig">Get cached (or eventually re-fetch) server-side config: getConfig</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getcachedconfig-array" name="getCachedConfig">Get cached server-side config: getCachedConfig</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getcall-int-call-array" name="getCall">Get call info: getCall</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#callstatus-int-id-int" name="callStatus">Get call status: callStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywordsDifference.html" name="messages.getEmojiKeywordsDifference">Get changed emoji keywords »: messages.getEmojiKeywordsDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppChangelog.html" name="help.getAppChangelog">Get changelog of current app.  : help.getAppChangelog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stats.getBroadcastStats.html" name="stats.getBroadcastStats">Get channel statistics: stats.getBroadcastStats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getAdminedPublicChannels.html" name="channels.getAdminedPublicChannels">Get channels/supergroups/geogroups we're admin in. Usually called when the user exceeds the limit for owned public channels/supergroups/geogroups, and the user is given the choice to remove one of his channels/supergroups/geogroups: channels.getAdminedPublicChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getCommonChats.html" name="messages.getCommonChats">Get chats in common with a user: messages.getCommonChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getCdnConfig.html" name="help.getCdnConfig">Get configuration for CDN file downloads: help.getCdnConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getContactIDs.html" name="contacts.getContactIDs">Get contact by telegram IDs: contacts.getContactIDs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getLocated.html" name="contacts.getLocated">Get contacts near you: contacts.getLocated</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#filegetcontents-string-url-string" name="fileGetContents">Get contents of remote file asynchronously: fileGetContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getOnlines.html" name="messages.getOnlines">Get count of online users in a chat: messages.getOnlines</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmaps-int" name="getMaps">Get current number of memory-mapped regions, UNIX only: getMaps</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#gethint-string" name="getHint">Get current password hint: getHint</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAccountTTL.html" name="account.getAccountTTL">Get days to live of account: account.getAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPeerDialogs.html" name="messages.getPeerDialogs">Get dialog info of specified peers: messages.getPeerDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogUnreadMarks.html" name="messages.getDialogUnreadMarks">Get dialogs manually marked as unread: messages.getDialogUnreadMarks</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getdhconfig-array" name="getDhConfig">Get diffie-hellman configuration: getDhConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDiscussionMessage.html" name="messages.getDiscussionMessage">Get discussion message from the associated discussion group of a channel to show it on top of the comment section, without actually joining the group: messages.getDiscussionMessage</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getdownloadinfo-mixed-messagemedia-array-ext-string-name-string-mime-string-size-int-inputfilelocation-array-key_fingerprint-string-key-string-iv-string-thumb_size-string" name="getDownloadInfo">Get download info of file: getDownloadInfo</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getpropicinfo-mixed-data-array" name="getPropicInfo">Get download info of the propic of a user: getPropicInfo</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getdownloadlink-danog-madelineproto-eventhandler-message-danog-madelineproto-eventhandler-media-array-string-media-string-scripturl-null-int-size-null-string-name-null-string-mime-null-string" name="getDownloadLink">Get download link of media file: getDownloadLink</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#geteventhandler-class-string-t-null-class-null-t-danog-madelineproto-ipc-eventhandlerproxy-__php_incomplete_class-null" name="getEventHandler">Get event handler (or plugin instance): getEventHandler</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getextensionfromlocation-mixed-location-string-default-string" name="getExtensionFromLocation">Get extension from file location: getExtensionFromLocation</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getextensionfrommime-string-mime-string" name="getExtensionFromMime">Get extension from mime type: getExtensionFromMime</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFavedStickers.html" name="messages.getFavedStickers">Get faved stickers: messages.getFavedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFeaturedStickers.html" name="messages.getFeaturedStickers">Get featured stickers: messages.getFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#end-array-what-mixed" name="end">Get final element of array: end</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getfolderid-mixed-id-int" name="getFolderId">Get folder ID from object: getFolderId</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogFilters.html" name="messages.getDialogFilters">Get folders: messages.getDialogFilters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getForumTopicsByID.html" name="channels.getForumTopicsByID">Get forum topics by their ID: channels.getForumTopicsByID</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getallmethods-array" name="getAllMethods">Get full list of MTProto and API methods: getAllMethods</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getGlobalPrivacySettings.html" name="account.getGlobalPrivacySettings">Get global privacy settings: account.getGlobalPrivacySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getGroupParticipants.html" name="phone.getGroupParticipants">Get group call participants: phone.getGroupParticipants</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getInlineGameHighScores.html" name="messages.getInlineGameHighScores">Get highscores of a game sent using an inline bot: messages.getInlineGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getGameHighScores.html" name="messages.getGameHighScores">Get highscores of a game: messages.getGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getInactiveChannels.html" name="channels.getInactiveChannels">Get inactive channels and supergroups: channels.getInactiveChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getGroupCallStreamChannels.html" name="phone.getGroupCallStreamChannels">Get info about RTMP streams in a group call or livestream.  : phone.getGroupCallStreamChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWallPaper.html" name="account.getWallPaper">Get info about a certain wallpaper: account.getWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getParticipant.html" name="channels.getParticipant">Get info about a channel/supergroup participant: channels.getParticipant</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getExportedChatInvite.html" name="messages.getExportedChatInvite">Get info about a chat invite: messages.getExportedChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getBankCardData.html" name="payments.getBankCardData">Get info about a credit card: payments.getBankCardData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getGroupCall.html" name="phone.getGroupCall">Get info about a group call: phone.getGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStickerSet.html" name="messages.getStickerSet">Get info about a stickerset: messages.getStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getDeepLinkInfo.html" name="help.getDeepLinkInfo">Get info about an unsupported deep link, see here for more info »: help.getDeepLinkInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAdminsWithInvites.html" name="messages.getAdminsWithInvites">Get info about chat invites generated by admins: messages.getAdminsWithInvites</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getfileinfo-mixed-constructor-array" name="getFileInfo">Get info about file: getFileInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getMultiWallPapers.html" name="account.getMultiWallPapers">Get info about multiple wallpapers: account.getMultiWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getExportedChatInvites.html" name="messages.getExportedChatInvites">Get info about the chat invites of a specific chat: messages.getExportedChatInvites</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#fullgetself-array-false" name="fullGetSelf">Get info about the logged-in user, not cached: fullGetSelf</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getChatInviteImporters.html" name="messages.getChatInviteImporters">Get info about the users that joined the chat using a specific chat invite: messages.getChatInviteImporters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLanguage.html" name="langpack.getLanguage">Get information about a language in a localization pack: langpack.getLanguage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLanguages.html" name="langpack.getLanguages">Get information about all languages in a localization pack: langpack.getLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getExtendedMedia.html" name="messages.getExtendedMedia">Get information about extended media: messages.getExtendedMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMaskStickers.html" name="messages.getMaskStickers">Get installed mask stickers: messages.getMaskStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getThemes.html" name="account.getThemes">Get installed themes: account.getThemes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getWebPage.html" name="messages.getWebPage">Get instant view page: messages.getWebPage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.exportMessageLink.html" name="channels.exportMessageLink">Get link and embed info of a message in a channel/supergroup: channels.exportMessageLink</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentLocations.html" name="messages.getRecentLocations">Get live location history of a certain user: messages.getRecentLocations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLangPack.html" name="langpack.getLangPack">Get localization pack strings: langpack.getLangPack</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywords.html" name="messages.getEmojiKeywords">Get localized emoji keywords »: messages.getEmojiKeywords</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getSupportName.html" name="help.getSupportName">Get localized name of the telegram support user: help.getSupportName</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.getBotInfo.html" name="bots.getBotInfo">Get localized name, about text and description of a bot (or of the current account, if called by a bot): bots.getBotInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAuthorizations.html" name="account.getAuthorizations">Get logged-in sessions: account.getAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getlogger-danog-madelineproto-logger" name="getLogger">Get logger: getLogger</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmaxmaps-int" name="getMaxMaps">Get maximum number of memory-mapped regions, UNIX only: getMaxMaps</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAutoDownloadSettings.html" name="account.getAutoDownloadSettings">Get media autodownload settings: account.getAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSplitRanges.html" name="messages.getSplitRanges">Get message ranges for saving the user's chat history: messages.getSplitRanges</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessageReactionsList.html" name="messages.getMessageReactionsList">Get message reaction list, along with the sender of each reaction: messages.getMessageReactionsList</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessagesReactions.html" name="messages.getMessagesReactions">Get message reactions »: messages.getMessagesReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stats.getMessageStats.html" name="stats.getMessageStats">Get message statistics: stats.getMessageStats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getReplies.html" name="messages.getReplies">Get messages in a reply thread: messages.getReplies</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmimefrombuffer-string-buffer-string" name="getMimeFromBuffer">Get mime type from buffer: getMimeFromBuffer</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmimefromextension-string-extension-string-default-string" name="getMimeFromExtension">Get mime type from file extension: getMimeFromExtension</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmimefromfile-string-file-string" name="getMimeFromFile">Get mime type of file: getMimeFromFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestUrlAuth.html" name="messages.requestUrlAuth">Get more info about a Seamless Telegram Login authorization request, for more info click here »: messages.requestUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getTopPeers.html" name="contacts.getTopPeers">Get most used peers: contacts.getTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getCountriesList.html" name="help.getCountriesList">Get name, ISO code, localized name and phone codes/patterns of all available countries: help.getCountriesList</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getmethodsnamespaced-array" name="getMethodsNamespaced">Get namespaced methods (method => namespace): getMethodsNamespaced</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getDifference.html" name="langpack.getDifference">Get new strings in language pack: langpack.getDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getPassportConfig.html" name="help.getPassportConfig">Get passport configuration: help.getPassportConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getPaymentReceipt.html" name="payments.getPaymentReceipt">Get payment receipt: payments.getPaymentReceipt</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPeerSettings.html" name="messages.getPeerSettings">Get peer settings: messages.getPeerSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getCallConfig.html" name="phone.getCallConfig">Get phone call configuration to be passed to libtgvoip's shared config: phone.getCallConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPinnedDialogs.html" name="messages.getPinnedDialogs">Get pinned dialogs: messages.getPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPollVotes.html" name="messages.getPollVotes">Get poll results for non-anonymous polls: messages.getPollVotes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPollResults.html" name="messages.getPollResults">Get poll results: messages.getPollResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getWebPagePreview.html" name="messages.getWebPagePreview">Get preview of webpage: messages.getWebPagePreview</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPrivacy.html" name="account.getPrivacy">Get privacy settings of current account: account.getPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#randomint-int-modulus-0-int" name="randomInt">Get random integer: randomInt</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentStickers.html" name="messages.getRecentStickers">Get recent stickers: messages.getRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getRecentEmojiStatuses.html" name="account.getRecentEmojiStatuses">Get recently used emoji statuses: account.getRecentEmojiStatuses</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentReactions.html" name="messages.getRecentReactions">Get recently used message reactions: messages.getRecentReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getRecentMeUrls.html" name="help.getRecentMeUrls">Get recently used t.me links: help.getRecentMeUrls</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSavedGifs.html" name="messages.getSavedGifs">Get saved GIFs: messages.getSavedGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getSecureValue.html" name="account.getSecureValue">Get saved Telegram Passport document, for more info see the passport docs »: account.getSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getSavedInfo.html" name="payments.getSavedInfo">Get saved payment information: payments.getSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getScheduledHistory.html" name="messages.getScheduledHistory">Get scheduled messages: messages.getScheduledHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getScheduledMessages.html" name="messages.getScheduledMessages">Get scheduled messages: messages.getScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#secretchatstatus-int-chat-int-one-of-danog-madelineproto-api-secret_empty-danog-madelineproto-api-secret_requested-danog-madelineproto-api-secret_ready" name="secretChatStatus">Get secret chat status: secretChatStatus</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getsecretchat-array-int-chat-array" name="getSecretChat">Get secret chat: getSecretChat</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#random-int-length-string" name="random">Get secure random string of specified length: random</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getContentSettings.html" name="account.getContentSettings">Get sensitive content settings: account.getContentSettings</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getsponsoredmessages-int-string-array-peer-array" name="getSponsoredMessages">Get sponsored messages for channel: getSponsoredMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAttachedStickers.html" name="messages.getAttachedStickers">Get stickers attached to a photo or video: messages.getAttachedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStickers.html" name="messages.getStickers">Get stickers by emoji: messages.getStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getStrings.html" name="langpack.getStrings">Get strings from a language pack: langpack.getStrings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSuggestedDialogFilters.html" name="messages.getSuggestedDialogFilters">Get suggested folders: messages.getSuggestedDialogFilters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stats.getMegagroupStats.html" name="stats.getMegagroupStats">Get supergroup statistics: stats.getMegagroupStats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getTmpPassword.html" name="account.getTmpPassword">Get temporary payment password: account.getTmpPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getAdminLog.html" name="channels.getAdminLog">Get the admin log of a channel/supergroup: channels.getAdminLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSearchCounters.html" name="messages.getSearchCounters">Get the number of results that would be found by a messages.search call with the same parameters: messages.getSearchCounters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getParticipants.html" name="channels.getParticipants">Get the participants of a supergroup/channel: channels.getParticipants</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getbroadcastprogress-int-id-danog-madelineproto-broadcast-progress" name="getBroadcastProgress">Get the progress of a currently running broadcast: getBroadcastProgress</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getTheme.html" name="account.getTheme">Get theme information: account.getTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getForumTopics.html" name="channels.getForumTopics">Get topics of a forum: channels.getForumTopics</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#gettype-mixed-id-danog-madelineproto-api-peer_type_" name="getType">Get type of peer: getType</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getUnreadMentions.html" name="messages.getUnreadMentions">Get unread messages where we were mentioned: messages.getUnreadMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getUnreadReactions.html" name="messages.getUnreadReactions">Get unread reactions to messages you sent: messages.getUnreadReactions</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getwebwarnings-string" name="getWebWarnings">Get various warnings to show to the user in the web UI: getWebWarnings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWebAuthorizations.html" name="account.getWebAuthorizations">Get web login widget authorizations: account.getWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessageReadParticipants.html" name="messages.getMessageReadParticipants">Get which users read a specific message: only available for groups and supergroups with less than chatreadmarksizethreshold members, read receipts will be stored for chatreadmarkexpireperiod seconds after the message was sent, see client configuration for more info »: messages.getMessageReadParticipants</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getNotifySettings.html" name="account.getNotifySettings">Gets current notification settings for a given user/group, from all users/all groups: account.getNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFeaturedEmojiStickers.html" name="messages.getFeaturedEmojiStickers">Gets featured custom emoji stickersets: messages.getFeaturedEmojiStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDefaultHistoryTTL.html" name="messages.getDefaultHistoryTTL">Gets the default value of the Time-To-Live setting, applied to all new chats: messages.getDefaultHistoryTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiStickers.html" name="messages.getEmojiStickers">Gets the list of currently installed custom emoji stickersets: messages.getEmojiStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.getBotMenuButton.html" name="bots.getBotMenuButton">Gets the menu button action for a given user or for all users, previously set using bots.setBotMenuButton; users can see this information in the botInfo constructor: bots.getBotMenuButton</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getTopReactions.html" name="messages.getTopReactions">Got popular message reactions: messages.getTopReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.hidePromoData.html" name="help.hidePromoData">Hide MTProxy/Public Service Announcement information: help.hidePromoData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleParticipantsHidden.html" name="channels.toggleParticipantsHidden">Hide or display the participants list in a supergroup: channels.toggleParticipantsHidden</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.togglePreHistoryHidden.html" name="channels.togglePreHistoryHidden">Hide/unhide message history for new channel/supergroup users: channels.togglePreHistoryHidden</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.acceptContact.html" name="contacts.acceptContact">If the peer settings of a new user allow us to add them as contact, add that user as contact: contacts.acceptContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotShippingResults.html" name="messages.setBotShippingResults">If you sent an invoice requesting a shipping address and the parameter is_flexible was specified, the bot will receive an updateBotShippingQuery update. Use this method to reply to shipping queries: messages.setBotShippingResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.joinChatlistInvite.html" name="chatlists.joinChatlistInvite">Import a chat folder deep link », joining some or all the chats in the folder: chatlists.joinChatlistInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.importChatInvite.html" name="messages.importChatInvite">Import a chat invite and join a private chat/supergroup/channel: messages.importChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#importauthorization-array-int-string-authorization-int-maindcid-array" name="importAuthorization">Import authorization: importAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.initHistoryImport.html" name="messages.initHistoryImport">Import chat history from a foreign chat app into a specific Telegram chat, click here for more info about imported chats »: messages.initHistoryImport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.importContacts.html" name="contacts.importContacts">Imports contacts: saves a full list on the server, adds already registered contacts to the contact list, returns added contacts and their info: contacts.importContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.prolongWebView.html" name="messages.prolongWebView">Indicate to the server (from the user side) that the user is still using a web app: messages.prolongWebView</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#inflatestripped-string-stripped-string" name="inflateStripped">Inflate stripped photosize to full JPG payload: inflateStripped</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.assignAppStoreTransaction.html" name="payments.assignAppStoreTransaction">Informs server about a purchase made through the App Store: for official applications only: payments.assignAppStoreTransaction</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.assignPlayMarketTransaction.html" name="payments.assignPlayMarketTransaction">Informs server about a purchase made through the Play Store: for official applications only: payments.assignPlayMarketTransaction</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.setBotUpdatesStatus.html" name="help.setBotUpdatesStatus">Informs the server about the number of pending bot updates if they haven't been processed for a long time; for bots only: help.setBotUpdatesStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.initTakeoutSession.html" name="account.initTakeoutSession">Initialize account takeout session: account.initTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/initConnection.html" name="initConnection">Initialize connection: initConnection</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#initselfrestart-void" name="initSelfRestart">Initialize self-restart hack: initSelfRestart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetPassword.html" name="account.resetPassword">Initiate a 2FA password reset: can only be used if the user is already logged-in, see here for more info »: account.resetPassword</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#qrlogin-danog-madelineproto-tl-types-loginqrcode" name="qrLogin">Initiates QR code login: qrLogin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.installStickerSet.html" name="messages.installStickerSet">Install a stickerset: messages.installStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.installTheme.html" name="account.installTheme">Install a theme: account.installTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.installWallPaper.html" name="account.installWallPaper">Install wallpaper: account.installWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveWallPaper.html" name="account.saveWallPaper">Install/uninstall wallpaper: account.saveWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.updateProfilePhoto.html" name="photos.updateProfilePhoto">Installs a previously uploaded photo as a profile photo: photos.updateProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#processdownloadserverping-string-path-string-payload-void" name="processDownloadServerPing">Internal endpoint used by the download server: processDownloadServerPing</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.editUserInfo.html" name="help.editUserInfo">Internal use: help.editUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.inviteToGroupCall.html" name="phone.inviteToGroupCall">Invite a set of users to a group call: phone.inviteToGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.inviteToChannel.html" name="channels.inviteToChannel">Invite users to a channel/supergroup: channels.inviteToChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithTakeout.html" name="invokeWithTakeout">Invoke a method within a takeout session: invokeWithTakeout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithoutUpdates.html" name="invokeWithoutUpdates">Invoke a request without subscribing the used connection for updates (this is enabled by default for file queries): invokeWithoutUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithLayer.html" name="invokeWithLayer">Invoke the specified query using the specified API layer: invokeWithLayer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithMessagesRange.html" name="invokeWithMessagesRange">Invoke with the given message range: invokeWithMessagesRange</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsgs.html" name="invokeAfterMsgs">Invokes a query after a successful completion of previous queries: invokeAfterMsgs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsg.html" name="invokeAfterMsg">Invokes a query after successful completion of one of the previous queries: invokeAfterMsg</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.joinChannel.html" name="channels.joinChannel">Join a channel/supergroup: channels.joinChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.joinGroupCall.html" name="phone.joinGroupCall">Join a group call: phone.joinGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.joinChatlistUpdates.html" name="chatlists.joinChatlistUpdates">Join channels and supergroups recently added to a chat folder deep link »: chatlists.joinChatlistUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.leaveChannel.html" name="channels.leaveChannel">Leave a channel/supergroup: channels.leaveChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.leaveGroupCall.html" name="phone.leaveGroupCall">Leave a group call: phone.leaveGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.getExportedInvites.html" name="chatlists.getExportedInvites">List all chat folder deep links » associated to a folder: chatlists.getExportedInvites</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stats.loadAsyncGraph.html" name="stats.loadAsyncGraph">Load channel statistics graph asynchronously: stats.loadAsyncGraph</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#start-mixed" name="start">Log in to telegram (via CLI or web): start</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetAuthorization.html" name="account.resetAuthorization">Log out an active authorized session by its hash: account.resetAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWebAuthorization.html" name="account.resetWebAuthorization">Log out an active web telegram login session: account.resetWebAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#logger-mixed-param-int-level-danog-madelineproto-logger-notice-string-file-void" name="logger">Logger: logger</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#botlogin-string-token-array" name="botLogin">Login as bot: botLogin</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#phonelogin-string-number-int-sms_type-5-mixed" name="phoneLogin">Login as user: phoneLogin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.importWebTokenAuthorization.html" name="auth.importWebTokenAuthorization">Login by importing an authorization token: auth.importWebTokenAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.importLoginToken.html" name="auth.importLoginToken">Login using a redirected login token, generated in case of DC mismatch during QR code login: auth.importLoginToken</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#logout-void" name="logout">Logout the session: logout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchCustomEmoji.html" name="messages.searchCustomEmoji">Look for custom emojis associated to a UTF8 emoji: messages.searchCustomEmoji</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getTermsOfServiceUpdate.html" name="help.getTermsOfServiceUpdate">Look for updates of telegram's terms of service: help.getTermsOfServiceUpdate</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#mtprotototd-mixed-params-mixed" name="MTProtoToTd">MTProto to TD params: MTProtoToTd</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#mtprotototdcli-mixed-params-mixed" name="MTProtoToTdcli">MTProto to TDCLI params: MTProtoToTdcli</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatAdmin.html" name="messages.editChatAdmin">Make a user admin in a basic group: messages.editChatAdmin</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#htmltomessageentities-string-html-danog-madelineproto-tl-conversion-domentities-object-containing-message-and-entities" name="htmlToMessageEntities">Manually convert HTML to a message and a set of entities: htmlToMessageEntities</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#markdowntomessageentities-string-markdown-danog-madelineproto-tl-conversion-markdownentities-object-containing-message-and-entities" name="markdownToMessageEntities">Manually convert markdown to a message and a set of entities: markdownToMessageEntities</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.markDialogUnread.html" name="messages.markDialogUnread">Manually mark dialog as unread: messages.markDialogUnread</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.viewSponsoredMessage.html" name="channels.viewSponsoredMessage">Mark a specific sponsored message as read: channels.viewSponsoredMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readDiscussion.html" name="messages.readDiscussion">Mark a thread as read: messages.readDiscussion</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.readHistory.html" name="channels.readHistory">Mark channel/supergroup history as read: channels.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.readMessageContents.html" name="channels.readMessageContents">Mark channel/supergroup message contents as read: channels.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readMentions.html" name="messages.readMentions">Mark mentions as read: messages.readMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readReactions.html" name="messages.readReactions">Mark message reactions » as read: messages.readReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readFeaturedStickers.html" name="messages.readFeaturedStickers">Mark new featured stickers as read: messages.readFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.faveSticker.html" name="messages.faveSticker">Mark or unmark a sticker as favorite: messages.faveSticker</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#viewsponsoredmessage-int-array-peer-string-array-random_id-string-message-bool" name="viewSponsoredMessage">Mark sponsored message as read: viewSponsoredMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readHistory.html" name="messages.readHistory">Marks message history as read: messages.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readEncryptedHistory.html" name="messages.readEncryptedHistory">Marks message history within a secret chat as read: messages.readEncryptedHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getOldFeaturedStickers.html" name="messages.getOldFeaturedStickers">Method for fetching previously featured stickers: messages.getOldFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveAutoSaveSettings.html" name="account.saveAutoSaveSettings">Modify autosave settings: account.saveAutoSaveSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editAdmin.html" name="channels.editAdmin">Modify the admin rights of a user in a supergroup/channel: channels.editAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readMessageContents.html" name="messages.readMessageContents">Notifies the sender about the recipient having listened a voice message or watched a video: messages.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendScreenshotNotification.html" name="messages.sendScreenshotNotification">Notify the other user in a private chat that a screenshot of the chat was taken: messages.sendScreenshotNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.setSecureValueErrors.html" name="users.setSecureValueErrors">Notify the user that the sent passport data contains some errors The user will not be able to re-submit their Passport data to you until the errors are fixed (the contents of the field for which you returned the error must change): users.setSecureValueErrors</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getplugin-class-string-t-class-danog-madelineproto-plugineventhandler-danog-madelineproto-ipc-eventhandlerproxy-null" name="getPlugin">Obtain a certain event handler plugin instance: getPlugin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.getBotCommands.html" name="bots.getBotCommands">Obtain a list of bot commands for the specified bot scope and language code: bots.getBotCommands</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywordsLanguages.html" name="messages.getEmojiKeywordsLanguages">Obtain a list of related languages that must be used when fetching emoji keyword lists »: messages.getEmojiKeywordsLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAvailableReactions.html" name="messages.getAvailableReactions">Obtain available message reactions »: messages.getAvailableReactions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPassword.html" name="account.getPassword">Obtain configuration for two-factor authorization with password: account.getPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.checkChatlistInvite.html" name="chatlists.checkChatlistInvite">Obtain information about a chat folder deep link »: chatlists.checkChatlistInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getBotApp.html" name="messages.getBotApp">Obtain information about a named bot web app: messages.getBotApp</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getwebapitemplate-string" name="getWebAPITemplate">Obtain the API ID UI template: getWebAPITemplate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.importContactToken.html" name="contacts.importContactToken">Obtain user info from a temporary profile link: contacts.importContactToken</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stats.getMessagePublicForwards.html" name="stats.getMessagePublicForwards">Obtains a list of messages, indicating to which other public channels was a channel message forwarded.  : stats.getMessagePublicForwards</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getSendAs.html" name="channels.getSendAs">Obtains a list of peers that can be used to send messages in a specific group: channels.getSendAs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.checkHistoryImport.html" name="messages.checkHistoryImport">Obtains information about a chat export file, generated by a foreign chat app, click here for more info about imported chats »: messages.checkHistoryImport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotPrecheckoutResults.html" name="messages.setBotPrecheckoutResults">Once the user has confirmed their payment and shipping details, the bot receives an updateBotPrecheckoutQuery update.  : messages.setBotPrecheckoutResults</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getupdates-array-offset-int-limit-int-timeout-float-params-list-array-update_id-mixed-update-mixed" name="getUpdates">Only useful when consuming MadelineProto updates through an API in another language (like Javascript), **absolutely not recommended when directly writing MadelineProto bots**: getUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestAppWebView.html" name="messages.requestAppWebView">Open a bot web app from a named bot web app deep link, sending over user information after user confirmation: messages.requestAppWebView</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestWebView.html" name="messages.requestWebView">Open a bot web app, sending over user information after user confirmation: messages.requestWebView</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestSimpleWebView.html" name="messages.requestSimpleWebView">Open a bot web app: messages.requestSimpleWebView</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#openfileappendonly-string-path-amp-file-file" name="openFileAppendOnly">Opens a file in append-only mode: openFileAppendOnly</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.receivedCall.html" name="phone.receivedCall">Optional: notify the server that the user is currently busy in a call: this will automatically refuse all incoming phone calls until the current phone call is ended: phone.receivedCall</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#updatesettings-settingsabstract-settings-void" name="updateSettings">Parse, update and store settings: updateSettings</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#validateeventhandlerclass-class-string-eventhandler-class-list-eventhandlerissue" name="validateEventHandlerClass">Perform static analysis on a certain event handler class, to make sure it satisfies some performance requirements: validateEventHandlerClass</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.updatePinnedMessage.html" name="messages.updatePinnedMessage">Pin a message: messages.updatePinnedMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.updatePinnedForumTopic.html" name="channels.updatePinnedForumTopic">Pin or unpin forum topics: channels.updatePinnedForumTopic</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.toggleDialogPin.html" name="messages.toggleDialogPin">Pin/unpin a dialog: messages.toggleDialogPin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getMessages.html" name="channels.getMessages">Please use the event handler: channels.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getHistory.html" name="messages.getHistory">Please use the event handler: messages.getHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessages.html" name="messages.getMessages">Please use the event handler: messages.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#posmod-int-a-int-b-int" name="posmod">Positive modulo: posmod</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getBotCallbackAnswer.html" name="messages.getBotCallbackAnswer">Press an inline callback button and get a callback answer from the bot: messages.getBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getInlineBotResults.html" name="messages.getInlineBotResults">Query an inline bot: messages.getInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.setCallRating.html" name="phone.setCallRating">Rate a call, returns info about the rating message sent to the official VoIP bot: phone.setCallRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.rateTranscribedAudio.html" name="messages.rateTranscribedAudio">Rate transcribed voice message: messages.rateTranscribedAudio</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendReaction.html" name="messages.sendReaction">React to message: messages.sendReaction</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#refreshfullpeercache-mixed-id-void" name="refreshFullPeerCache">Refresh full peer cache for a certain peer: refreshFullPeerCache</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#refreshpeercache-mixed-ids-void" name="refreshPeerCache">Refresh peer cache for a certain peer: refreshPeerCache</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.registerDevice.html" name="account.registerDevice">Register device to receive PUSH notifications: account.registerDevice</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#rekey-int-chat-string" name="rekey">Rekey secret chat: rekey</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.removeStickerFromSet.html" name="stickers.removeStickerFromSet">Remove a sticker from the set where it belongs, bots only. The sticker set must have been created by the bot: stickers.removeStickerFromSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.renameStickerSet.html" name="stickers.renameStickerSet">Renames a stickerset, bots only: stickers.renameStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.reorderUsernames.html" name="channels.reorderUsernames">Reorder active usernames: channels.reorderUsernames</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.updateDialogFiltersOrder.html" name="messages.updateDialogFiltersOrder">Reorder folders: messages.updateDialogFiltersOrder</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reorderStickerSets.html" name="messages.reorderStickerSets">Reorder installed stickersets: messages.reorderStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reorderPinnedDialogs.html" name="messages.reorderPinnedDialogs">Reorder pinned dialogs: messages.reorderPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.reorderPinnedForumTopics.html" name="channels.reorderPinnedForumTopics">Reorder pinned forum topics: channels.reorderPinnedForumTopics</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.reorderUsernames.html" name="bots.reorderUsernames">Reorder usernames associated to a bot we own: bots.reorderUsernames</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.reorderUsernames.html" name="account.reorderUsernames">Reorder usernames associated with the currently logged-in user: account.reorderUsernames</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.report.html" name="messages.report">Report a message in a chat for violation of telegram's Terms of Service: messages.report</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportReaction.html" name="messages.reportReaction">Report a message reaction: messages.reportReaction</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.reportAntiSpamFalsePositive.html" name="channels.reportAntiSpamFalsePositive">Report a native antispam false positive: channels.reportAntiSpamFalsePositive</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportSpam.html" name="messages.reportSpam">Report a new incoming chat for spam, if the peer settings of the chat allow us to do that: messages.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.reportPeer.html" name="account.reportPeer">Report a peer for violation of telegram's Terms of Service: account.reportPeer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.reportProfilePhoto.html" name="account.reportProfilePhoto">Report a profile photo of a dialog: account.reportProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportEncryptedSpam.html" name="messages.reportEncryptedSpam">Report a secret chat for spam: messages.reportEncryptedSpam</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#report-string-message-string-parsemode-void" name="report">Report an error to the previously set peer: report</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#reportmemoryprofile-void" name="reportMemoryProfile">Report memory profile with memprof: reportMemoryProfile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.reportSpam.html" name="channels.reportSpam">Reports some messages from a user in a supergroup as spam; requires administrator rights in the supergroup: channels.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiStatusGroups.html" name="messages.getEmojiStatusGroups">Represents a list of emoji categories, to be used when selecting custom emojis to set as custom emoji status: messages.getEmojiStatusGroups</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiProfilePhotoGroups.html" name="messages.getEmojiProfilePhotoGroups">Represents a list of emoji categories, to be used when selecting custom emojis to set as profile picture: messages.getEmojiProfilePhotoGroups</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiGroups.html" name="messages.getEmojiGroups">Represents a list of emoji categories, to be used when selecting custom emojis: messages.getEmojiGroups</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#requestcall-mixed-user-mixed" name="requestCall">Request VoIP call: requestCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.requestFirebaseSms.html" name="auth.requestFirebaseSms">Request an SMS code via Firebase: auth.requestFirebaseSms</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.requestPasswordRecovery.html" name="auth.requestPasswordRecovery">Request recovery code of a 2FA password, only for accounts with a recovery email configured: auth.requestPasswordRecovery</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#requestsecretchat-mixed-user-mixed" name="requestSecretChat">Request secret chat: requestSecretChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resendPasswordEmail.html" name="account.resendPasswordEmail">Resend the code to verify an email to use as 2FA recovery method: account.resendPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resendCode.html" name="auth.resendCode">Resend the login code via another medium, the phone code type is determined by the return value of the previous auth.sendCode/auth.resendCode: see login for more info: auth.resendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWebAuthorizations.html" name="account.resetWebAuthorizations">Reset all active web telegram login sessions: account.resetWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resetTopPeerRating.html" name="contacts.resetTopPeerRating">Reset rating of top peer: contacts.resetTopPeerRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.recoverPassword.html" name="auth.recoverPassword">Reset the 2FA password using the recovery code sent using auth.requestPasswordRecovery: auth.recoverPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resetLoginEmail.html" name="auth.resetLoginEmail">Reset the login email »: auth.resetLoginEmail</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#resetupdatestate-void" name="resetUpdateState">Reset the update state and fetch all updates from the beginning: resetUpdateState</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetNotifySettings.html" name="account.resetNotifySettings">Resets all notification settings from users and groups: account.resetNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resolvePhone.html" name="contacts.resolvePhone">Resolve a phone number to get user info, if their privacy settings allow it: contacts.resolvePhone</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#restart-void" name="restart">Restart update loop: restart</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#rethrow-throwable-e-void" name="rethrow">Rethrow exception into event loop: rethrow</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllDrafts.html" name="messages.getAllDrafts">Return all message drafts.  : messages.getAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getsettings-danog-madelineproto-settings" name="getSettings">Return current settings: getSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAuthorizationForm.html" name="account.getAuthorizationForm">Returns a Telegram Passport authorization form for sharing data with a service: account.getAuthorizationForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWallPapers.html" name="account.getWallPapers">Returns a list of available wallpapers: account.getWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiURL.html" name="messages.getEmojiURL">Returns an HTTP URL which can be used to automatically log in into translation platform and suggest new emoji keywords ». The URL will be valid for 30 seconds after generation: messages.getEmojiURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAttachMenuBot.html" name="messages.getAttachMenuBot">Returns attachment menu entry for a bot web app that can be launched from the attachment menu »: messages.getAttachMenuBot</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getChats.html" name="messages.getChats">Returns chat basic info on their IDs: messages.getChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getWebFile.html" name="upload.getWebFile">Returns content of a web file, by proxying the request through telegram, see the webfile docs for more info: upload.getWebFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getConfig.html" name="help.getConfig">Returns current configuration, including data center configuration: help.getConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.search.html" name="messages.search">Returns found messages: messages.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/chatlists.getLeaveChatlistSuggestions.html" name="chatlists.getLeaveChatlistSuggestions">Returns identifiers of pinned or always included chats from a chat folder imported using a chat folder deep link », which are suggested to be left when the chat folder is deleted: chatlists.getLeaveChatlistSuggestions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getNearestDc.html" name="help.getNearestDc">Returns info on data center nearest to the user: help.getNearestDc</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSearchResultsCalendar.html" name="messages.getSearchResultsCalendar">Returns information about the next messages of the specified type in the chat split by days: messages.getSearchResultsCalendar</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppUpdate.html" name="help.getAppUpdate">Returns information on update availability for the current application: help.getAppUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAttachMenuBots.html" name="messages.getAttachMenuBots">Returns installed attachment menu bot web apps »: messages.getAttachMenuBots</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getNotifyExceptions.html" name="account.getNotifyExceptions">Returns list of chats with non-default notification settings: account.getNotifyExceptions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getInviteText.html" name="help.getInviteText">Returns localized text of a text message with an invitation: help.getInviteText</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSearchResultsPositions.html" name="messages.getSearchResultsPositions">Returns sparse positions of messages of the specified type in the chat to be used for shared media scroll implementation: messages.getSearchResultsPositions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogs.html" name="messages.getDialogs">Returns the current user dialog list: messages.getDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getContacts.html" name="contacts.getContacts">Returns the current user's contact list: contacts.getContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getBlocked.html" name="contacts.getBlocked">Returns the list of blocked users: contacts.getBlocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getStatuses.html" name="contacts.getStatuses">Returns the list of contact statuses: contacts.getStatuses</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.getUserPhotos.html" name="photos.getUserPhotos">Returns the list of user photos: photos.getUserPhotos</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getsessionname-string" name="getSessionName">Returns the session name: getSessionName</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getSupport.html" name="help.getSupport">Returns the support user for the "ask a question" feature: help.getSupport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.search.html" name="contacts.search">Returns users found by username substring: contacts.search</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isselfbot-bool" name="isSelfBot">Returns whether the current user is a bot: isSelfBot</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#ispremium-bool" name="isPremium">Returns whether the current user is a premium user, cached: isPremium</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isselfuser-bool" name="isSelfUser">Returns whether the current user is a user: isSelfUser</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#uploadfromtgfile-mixed-media-callable-cb-null-bool-encrypted-false-mixed" name="uploadFromTgfile">Reupload telegram file: uploadFromTgfile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveDraft.html" name="messages.saveDraft">Save a message draft associated to a chat: messages.saveDraft</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveTheme.html" name="account.saveTheme">Save a theme: account.saveTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveRingtone.html" name="account.saveRingtone">Save or remove saved notification sound: account.saveRingtone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.saveCallLog.html" name="phone.saveCallLog">Save phone call debug information: phone.saveCallLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.saveAppLog.html" name="help.saveAppLog">Saves logs of application on the server: help.saveAppLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchGlobal.html" name="messages.searchGlobal">Search for messages and peers globally: messages.searchGlobal</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchStickerSets.html" name="messages.searchStickerSets">Search for stickersets: messages.searchStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveSecureValue.html" name="account.saveSecureValue">Securely save Telegram Passport document, for more info see the passport docs »: account.saveSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.sendSignalingData.html" name="phone.sendSignalingData">Send VoIP signaling data: phone.sendSignalingData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendBotRequestedPeer.html" name="messages.sendBotRequestedPeer">Send a chosen peer, as requested by a keyboardButtonRequestPeer button: messages.sendBotRequestedPeer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMedia.html" name="messages.sendMedia">Send a media: messages.sendMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendInlineBotResult.html" name="messages.sendInlineBotResult">Send a result obtained using messages.getInlineBotResults: messages.sendInlineBotResult</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMultiMedia.html" name="messages.sendMultiMedia">Send an album or grouped media: messages.sendMultiMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendVerifyEmailCode.html" name="account.sendVerifyEmailCode">Send an email verification code: account.sendVerifyEmailCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.sendPaymentForm.html" name="payments.sendPaymentForm">Send compiled payment form: payments.sendPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendConfirmPhoneCode.html" name="account.sendConfirmPhoneCode">Send confirmation code to cancel account deletion, for more info click here »: account.sendConfirmPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.saveCallDebug.html" name="phone.saveCallDebug">Send phone call debug data to server: phone.saveCallDebug</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendScheduledMessages.html" name="messages.sendScheduledMessages">Send scheduled messages right away: messages.sendScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendVerifyPhoneCode.html" name="account.sendVerifyPhoneCode">Send the verification phone code for telegram passport: account.sendVerifyPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setEncryptedTyping.html" name="messages.setEncryptedTyping">Send typing event by the current user to a secret chat: messages.setEncryptedTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.acceptAuthorization.html" name="account.acceptAuthorization">Sends a Telegram Passport authorization form, effectively sharing data with the service: account.acceptAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setTyping.html" name="messages.setTyping">Sends a current user typing event (see SendMessageAction for all event types) to a conversation partner or group: messages.setTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.sendCustomRequest.html" name="bots.sendCustomRequest">Sends a custom request; for bots only: bots.sendCustomRequest</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#senddocument-int-string-peer-danog-madelineproto-eventhandler-message-danog-madelineproto-eventhandler-media-localfile-remoteurl-botapifileid-amp-bytestream-readablestream-file-danog-madelineproto-eventhandler-message-danog-madelineproto-eventhandler-media-localfile-remoteurl-botapifileid-amp-bytestream-readablestream-null-thumb-null-string-caption-parsemode-parsemode-danog-madelineproto-parsemode-text-callable-callback-null-string-filename-null-string-mimetype-null-int-ttl-null-bool-spoiler-false-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message" name="sendDocument">Sends a document: sendDocument</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#broadcastmessages-array-messages-danog-madelineproto-broadcast-filter-filter-null-bool-pin-false-int" name="broadcastMessages">Sends a list of messages to all peers (users, chats, channels) of the bot: broadcastMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMessage.html" name="messages.sendMessage">Sends a message to a chat: messages.sendMessage</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendmessagetoadmins-string-message-parsemode-parsemode-danog-madelineproto-parsemode-text-array-null-replymarkup-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-nowebpage-false-list-danog-madelineproto-eventhandler-message" name="sendMessageToAdmins">Sends a message to all report peers (admins of the bot): sendMessageToAdmins</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncryptedFile.html" name="messages.sendEncryptedFile">Sends a message with a file attachment to a secret chat: messages.sendEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendmessage-int-string-peer-string-message-parsemode-parsemode-danog-madelineproto-parsemode-text-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-nowebpage-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message" name="sendMessage">Sends a message: sendMessage</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendphoto-int-string-peer-danog-madelineproto-eventhandler-message-danog-madelineproto-eventhandler-media-localfile-remoteurl-botapifileid-amp-bytestream-readablestream-file-string-caption-parsemode-parsemode-danog-madelineproto-parsemode-text-callable-callback-null-string-filename-null-int-ttl-null-bool-spoiler-false-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message" name="sendPhoto">Sends a photo: sendPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncryptedService.html" name="messages.sendEncryptedService">Sends a service message to a secret chat: messages.sendEncryptedService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncrypted.html" name="messages.sendEncrypted">Sends a text message to a secret chat: messages.sendEncrypted</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendcustomevent-mixed-payload-void" name="sendCustomEvent">Sends an updateCustomEvent update to the event handler: sendCustomEvent</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#setnoop-void" name="setNoop">Set NOOP update handler, ignoring all updates: setNoop</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setChatWallPaper.html" name="messages.setChatWallPaper">Set a custom wallpaper » in a specific private chat with another user: messages.setChatWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setAccountTTL.html" name="account.setAccountTTL">Set account self-destruction period: account.setAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateEmojiStatus.html" name="account.updateEmojiStatus">Set an emoji status: account.updateEmojiStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.setBotCommands.html" name="bots.setBotCommands">Set bot command list: bots.setBotCommands</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setGlobalPrivacySettings.html" name="account.setGlobalPrivacySettings">Set global privacy settings: account.setGlobalPrivacySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.setBotInfo.html" name="bots.setBotInfo">Set localized name, about text and description of a bot (or of the current account, if called by a bot): bots.setBotInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setHistoryTTL.html" name="messages.setHistoryTTL">Set maximum Time-To-Live of all messages in the specified chat: messages.setHistoryTTL</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#setreportpeers-int-string-int-string-userorid-void" name="setReportPeers">Set peer(s) where to send errors occurred in the event loop: setReportPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setContentSettings.html" name="account.setContentSettings">Set sensitive content settings (for viewing or hiding NSFW content): account.setContentSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.setStickerSetThumb.html" name="stickers.setStickerSetThumb">Set stickerset thumbnail: stickers.setStickerSetThumb</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#setwebapitemplate-string-template-void" name="setWebApiTemplate">Set the API ID UI template: setWebApiTemplate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotCallbackAnswer.html" name="messages.setBotCallbackAnswer">Set the callback answer to a user button press (bots only): messages.setBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.saveDefaultGroupCallJoinAs.html" name="phone.saveDefaultGroupCallJoinAs">Set the default peer that will be used to join a group call in a specific dialog: phone.saveDefaultGroupCallJoinAs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.setBotBroadcastDefaultAdminRights.html" name="bots.setBotBroadcastDefaultAdminRights">Set the default suggested admin rights for bots being added as admins to channels, see here for more info on how to handle them »: bots.setBotBroadcastDefaultAdminRights</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.setBotGroupDefaultAdminRights.html" name="bots.setBotGroupDefaultAdminRights">Set the default suggested admin rights for bots being added as admins to groups, see here for more info on how to handle them »: bots.setBotGroupDefaultAdminRights</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setAuthorizationTTL.html" name="account.setAuthorizationTTL">Set time-to-live of current session: account.setAuthorizationTTL</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#setwebhook-string-webhookurl-void" name="setWebhook">Set webhook update handler: setWebhook</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleJoinToSend.html" name="channels.toggleJoinToSend">Set whether all users should join a discussion group in order to comment on a post »: channels.toggleJoinToSend</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleJoinRequest.html" name="channels.toggleJoinRequest">Set whether all users should request admin approval to join the group »: channels.toggleJoinRequest</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.setBotMenuButton.html" name="bots.setBotMenuButton">Sets the menu button action » for a given user or for all users: bots.setBotMenuButton</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#setuplogger-void" name="setupLogger">Setup logger: setupLogger</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.hidePeerSettingsBar.html" name="messages.hidePeerSettingsBar">Should be called after the user hides the report spam/add as contact bar of a new chat, effectively prevents the user from executing the actions specified in the peer's settings: messages.hidePeerSettingsBar</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.togglePeerTranslations.html" name="messages.togglePeerTranslations">Show or hide the real-time chat translation popup for a certain chat: messages.togglePeerTranslations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.startBot.html" name="messages.startBot">Start a conversation with a bot using a deep linking parameter: messages.startBot</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.startScheduledGroupCall.html" name="phone.startScheduledGroupCall">Start a scheduled group call: phone.startScheduledGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#startandloopmulti-api-instances-class-string-eventhandler-class-string-eventhandler-eventhandler-void" name="startAndLoopMulti">Start multiple instances of MadelineProto and the event handlers (enables async): startAndLoopMulti</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.toggleGroupCallRecord.html" name="phone.toggleGroupCallRecord">Start or stop recording a group call: the recorded audio and video streams will be automatically sent to Saved messages (the chat with ourselves): phone.toggleGroupCallRecord</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.joinGroupCallPresentation.html" name="phone.joinGroupCallPresentation">Start screen sharing in a call: phone.joinGroupCallPresentation</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.blockFromReplies.html" name="contacts.blockFromReplies">Stop getting notifications about thread replies of a certain user in @replies: contacts.blockFromReplies</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.leaveGroupCallPresentation.html" name="phone.leaveGroupCallPresentation">Stop screen sharing in a group call: phone.leaveGroupCallPresentation</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#stop-void" name="stop">Stop update loop: stop</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#getcdnconfig-void" name="getCdnConfig">Store RSA keys for CDN datacenters: getCdnConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.validateRequestedInfo.html" name="payments.validateRequestedInfo">Submit requested order information for validation: payments.validateRequestedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.toggleGroupCallStartSubscription.html" name="phone.toggleGroupCallStartSubscription">Subscribe or unsubscribe to a scheduled group call: phone.toggleGroupCallStartSubscription</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#subscribetoupdates-mixed-channel-bool-false-if-we-were-already-subscribed" name="subscribeToUpdates">Subscribe to event handler updates for a channel/supergroup we're not a member of: subscribeToUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.suggestShortName.html" name="stickers.suggestShortName">Suggests a short name for a given stickerpack name: stickers.suggestShortName</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#mbstrsplit-string-text-int-length-string" name="mbStrSplit">Telegram UTF-8 multibyte split: mbStrSplit</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#mbsubstr-string-text-int-offset-null-int-length-null-string" name="mbSubstr">Telegram UTF-8 multibyte substring: mbSubstr</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.discardGroupCall.html" name="phone.discardGroupCall">Terminate a group call: phone.discardGroupCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendWebViewResultMessage.html" name="messages.sendWebViewResultMessage">Terminate webview interaction started with messages.requestWebView, sending the specified message to the chat on behalf of the user: messages.sendWebViewResultMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resetAuthorizations.html" name="auth.resetAuthorizations">Terminates all user's authorized sessions except for the current one: auth.resetAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#testfibers-int-fibercount-100000-array-maxfibers-int-realmemorymb-int-maps-int-maxmaps-int" name="testFibers">Test fibers: testFibers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setContactSignUpNotification.html" name="account.setContactSignUpNotification">Toggle contact sign up notifications: account.setContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleSlowMode.html" name="channels.toggleSlowMode">Toggle supergroup slow mode: if enabled, users will only be able to send one message every seconds seconds: channels.toggleSlowMode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.transcribeAudio.html" name="messages.transcribeAudio">Transcribe voice message: messages.transcribeAudio</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editCreator.html" name="channels.editCreator">Transfer channel ownership: channels.editCreator</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.translateText.html" name="messages.translateText">Translate a given text: messages.translateText</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.migrateChat.html" name="messages.migrateChat">Turn a basic group into a supergroup: messages.migrateChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uninstallStickerSet.html" name="messages.uninstallStickerSet">Uninstall a stickerset: messages.uninstallStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#unpacksignedint-string-value-int" name="unpackSignedInt">Unpack base256 signed int: unpackSignedInt</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#unpacksignedlongstring-string-int-array-value-string" name="unpackSignedLongString">Unpack base256 signed long to string: unpackSignedLongString</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#unpacksignedlong-string-value-int" name="unpackSignedLong">Unpack base256 signed long: unpackSignedLong</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#unpackdouble-string-value-float" name="unpackDouble">Unpack binary double: unpackDouble</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#unpackfileid-string-fileid-array-unpacked-file-id" name="unpackFileId">Unpack bot API file ID: unpackFileId</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.unpinAllMessages.html" name="messages.unpinAllMessages">Unpin all pinned messages: messages.unpinAllMessages</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#unseteventhandler-void" name="unsetEventHandler">Unset event handler: unsetEventHandler</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.updateDialogFilter.html" name="messages.updateDialogFilter">Update folder: messages.updateDialogFilter</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.changeSticker.html" name="stickers.changeSticker">Update the keywords, emojis or mask coordinates of a sticker, bots only: stickers.changeSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateTheme.html" name="account.updateTheme">Update theme: account.updateTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.uploadProfilePhoto.html" name="photos.uploadProfilePhoto">Updates current user profile photo: photos.uploadProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateStatus.html" name="account.updateStatus">Updates online user status: account.updateStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateProfile.html" name="account.updateProfile">Updates user profile: account.updateProfile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.uploadContactProfilePhoto.html" name="photos.uploadContactProfilePhoto">Upload a custom profile picture for a contact, or suggest a new profile picture to a contact: photos.uploadContactProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadMedia.html" name="messages.uploadMedia">Upload a file and associate it to a chat (without actually sending it to the chat): messages.uploadMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadImportedMedia.html" name="messages.uploadImportedMedia">Upload a media file associated with an imported chat, click here for more info »: messages.uploadImportedMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadEncryptedFile.html" name="messages.uploadEncryptedFile">Upload encrypted file and associate it to a secret chat: messages.uploadEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#uploadfromurl-string-filecallbackinterface-url-int-size-0-string-filename-callable-cb-null-bool-encrypted-false-mixed" name="uploadFromUrl">Upload file from URL: uploadFromUrl</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#uploadfromcallable-mixed-callable-int-size-string-mime-string-filename-callable-cb-null-bool-seekable-true-bool-encrypted-false-mixed" name="uploadFromCallable">Upload file from callable: uploadFromCallable</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#uploadfromstream-mixed-stream-int-size-string-mime-string-filename-callable-cb-null-bool-encrypted-false-mixed" name="uploadFromStream">Upload file from stream: uploadFromStream</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#uploadencrypted-filecallbackinterface-string-array-file-string-filename-callable-cb-null-mixed" name="uploadEncrypted">Upload file to secret chat: uploadEncrypted</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#upload-filecallbackinterface-localfile-remoteurl-botapifileid-string-array-resource-file-string-filename-callable-cb-null-bool-encrypted-false-mixed" name="upload">Upload file: upload</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadRingtone.html" name="account.uploadRingtone">Upload notification sound, use account.saveRingtone to convert it and add it to the list of saved notification sounds: account.uploadRingtone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadTheme.html" name="account.uploadTheme">Upload theme: account.uploadTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setGameScore.html" name="messages.setGameScore">Use this method to set the score of the specified user in a game sent as a normal message (bots only): messages.setGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setInlineGameScore.html" name="messages.setInlineGameScore">Use this method to set the score of the specified user in a game sent as an inline message (bots only): messages.setInlineGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.acceptUrlAuth.html" name="messages.acceptUrlAuth">Use this to accept a Seamless Telegram Login authorization request, for more info click here »: messages.acceptUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendWebViewData.html" name="messages.sendWebViewData">Used by the user to relay data from an opened reply keyboard bot web app to the bot that owns it: messages.sendWebViewData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.checkUsername.html" name="account.checkUsername">Validates a username and checks availability: account.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendChangePhoneCode.html" name="account.sendChangePhoneCode">Verify a new phone number to associate to the current account: account.sendChangePhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.verifyPhone.html" name="account.verifyPhone">Verify a phone number for telegram passport: account.verifyPhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.verifyEmail.html" name="account.verifyEmail">Verify an email address: account.verifyEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.confirmPasswordEmail.html" name="account.confirmPasswordEmail">Verify an email to use as 2FA recovery method: account.confirmPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchSentMedia.html" name="messages.searchSentMedia">View and search recently sent media.  : messages.searchSentMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendVote.html" name="messages.sendVote">Vote in a poll: messages.sendVote</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateDeviceLocked.html" name="account.updateDeviceLocked">When client-side passcode lock feature is enabled, will not show message texts in incoming PUSH notifications: account.updateDeviceLocked</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#fullchatlastupdated-mixed-id-int" name="fullChatLastUpdated">When were full info for this chat last cached: fullChatLastUpdated</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getContactSignUpNotification.html" name="account.getContactSignUpNotification">Whether the user will receive notifications when contacts sign up: account.getContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isaltervista-bool" name="isAltervista">Whether this is altervista: isAltervista</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isipc-bool" name="isIpc">Whether we're an IPC client instance: isIpc</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#isipcworker-bool" name="isIpcWorker">Whether we're an IPC server process (as opposed to an event handler): isIpcWorker</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#istestmode-bool" name="isTestMode">Whether we're currently connected to the test DCs: isTestMode</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#wrapmessage-array-message-danog-madelineproto-eventhandler-abstractmessage" name="wrapMessage">Wrap a Message constructor into an abstract Message object: wrapMessage</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#wrapmedia-array-media-bool-protected-false-danog-madelineproto-eventhandler-media" name="wrapMedia">Wrap a media constructor into an abstract Media object: wrapMedia</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#wrapupdate-array-update-danog-madelineproto-eventhandler-update" name="wrapUpdate">Wrap an Update constructor into an abstract Update object: wrapUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.invalidateSignInCodes.html" name="account.invalidateSignInCodes">account.invalidateSignInCodes</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#base64urldecode-string-data-string" name="base64urlDecode">base64URL decode: base64urlDecode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.clickSponsoredMessage.html" name="channels.clickSponsoredMessage">channels.clickSponsoredMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.editCloseFriends.html" name="contacts.editCloseFriends">contacts.editCloseFriends</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.toggleStoriesHidden.html" name="contacts.toggleStoriesHidden">contacts.toggleStoriesHidden</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#rledecode-string-string-string" name="rleDecode">null-byte RLE decode: rleDecode</a>
    * <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#rleencode-string-string-string" name="rleEncode">null-byte RLE encode: rleEncode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.deleteStories.html" name="stories.deleteStories">stories.deleteStories</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.editStory.html" name="stories.editStory">stories.editStory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.exportStoryLink.html" name="stories.exportStoryLink">stories.exportStoryLink</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getAllReadUserStories.html" name="stories.getAllReadUserStories">stories.getAllReadUserStories</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getAllStories.html" name="stories.getAllStories">stories.getAllStories</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getPinnedStories.html" name="stories.getPinnedStories">stories.getPinnedStories</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getStoriesArchive.html" name="stories.getStoriesArchive">stories.getStoriesArchive</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getStoriesByID.html" name="stories.getStoriesByID">stories.getStoriesByID</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getStoriesViews.html" name="stories.getStoriesViews">stories.getStoriesViews</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getStoryViewsList.html" name="stories.getStoryViewsList">stories.getStoryViewsList</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.getUserStories.html" name="stories.getUserStories">stories.getUserStories</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.incrementStoryViews.html" name="stories.incrementStoryViews">stories.incrementStoryViews</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.readStories.html" name="stories.readStories">stories.readStories</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.report.html" name="stories.report">stories.report</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.sendStory.html" name="stories.sendStory">stories.sendStory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.toggleAllStoriesHidden.html" name="stories.toggleAllStoriesHidden">stories.toggleAllStoriesHidden</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stories.togglePinned.html" name="stories.togglePinned">stories.togglePinned</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.getStoriesMaxIDs.html" name="users.getStoriesMaxIDs">users.getStoriesMaxIDs</a>
* [Contributing](https://docs.madelineproto.xyz/docs/CONTRIB.html)
  * [Translation](https://docs.madelineproto.xyz/docs/CONTRIB.html#translation)
  * [Contribution guide](https://docs.madelineproto.xyz/docs/CONTRIB.html#contribution-guide)
  * [Credits](https://docs.madelineproto.xyz/docs/CONTRIB.html#credits)
* [Web templates for `$MadelineProto->start()`](https://docs.madelineproto.xyz/docs/TEMPLATES.html)

