Introducing MadelineProto's biggest update yet, 8.0.0-beta100!

This version introduces [plugins](https://docs.madelineproto.xyz/docs/PLUGINS.html), [bound methods](https://docs.madelineproto.xyz/docs/UPDATES.html#bound-methods), [filters](https://docs.madelineproto.xyz/docs/FILTERS.html), [a built-in cron system](https://docs.madelineproto.xyz/docs/UPDATES.html#cron), [IPC support for the event handler](https://docs.madelineproto.xyz/docs/UPDATES.html#persisting-data-and-ipc) and automatic static analysis for event handler code.

- [Plugins](https://docs.madelineproto.xyz/docs/PLUGINS.html)

To create a plugin, simply create an event handler that extends PluginEventHandler.  

For example, create a `plugins/Danogentili/PingPlugin.php` file:
```
<?php declare(strict_types=1);

namespace MadelinePlugin\Danogentili\PingPlugin;

use danog\MadelineProto\PluginEventHandler;
use danog\MadelineProto\EventHandler\Filter\FilterText;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;

class PingPlugin extends PluginEventHandler
{
    #[FilterCommand('echo')]
    public function echoCmd(Incoming & Message $message): void
    {
        // Contains the arguments of the command
        $args = $message->commandArgs;

        $message->reply($args[0] ?? '');
    }

    #[FilterRegex('/.*(mt?proto).*/i')]
    public function testRegex(Incoming & Message $message): void
    {
        $message->reply("Did you mean to write MadelineProto instead of ".$message->matches[1].'?');
    }

    #[FilterText('ping')]
    public function pingCommand(Incoming&Message $message): void
    {
        $message->reply("Pong");
    }
}
```

And use a [plugin base](https://raw.githubusercontent.com/danog/MadelineProto/v8/examples/PluginBase.php) to run all plugins included in the `plugins` folder.  

See the [documentation](https://docs.madelineproto.xyz/docs/PLUGINS.html) for more info on how to create MadelineProto plugins!

- [Message](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/EventHandler/Message.html) objects with bound methods

Both plugins and normal bots can make use of [bound update methods](https://docs.madelineproto.xyz/docs/UPDATES.html#bound-methods) like `reply()`, `delete()`, `getReply()`, `getHTML()` and simplified properties like `chatId`, `senderId`, `command`, `commandArgs` and many more, see the [documentation](https://docs.madelineproto.xyz/docs/UPDATES.html#bound-methods) for more info!

- [Filters](https://docs.madelineproto.xyz/docs/FILTERS.html)

Plugins and bots can now use three different filtering systems, to easily receive only updates satisfying certain conditions (incoming/outgoing, from group, channel, private, from an admin or a specific peer, with an audio/sticker/..., satisfying a certain regex or a certain /command, and much more!), [see the documentation](https://docs.madelineproto.xyz/docs/FILTERS.html) for more info!

- [Built-in cron system](https://docs.madelineproto.xyz/docs/UPDATES.html#cron)

All event handler methods marked by the `Cron` attribute are now automatically invoked by MadelineProto every `period` seconds:

```
use danog\MadelineProto\EventHandler\Attributes\Cron;

class MyEventHandler extends SimpleEventHandler
{
    /**
     * This cron function will be executed forever, every 60 seconds.
     */
    #[Cron(period: 60.0)]
    public function cron1(): void
    {
        $this->sendMessageToAdmins("The bot is online, current time ".date(DATE_RFC850)."!");
    }
}
```

See the [documentation](https://docs.madelineproto.xyz/docs/UPDATES.html#cron) for more info!

- [IPC support for the event handler](https://docs.madelineproto.xyz/docs/UPDATES.html#persisting-data-and-ipc)

You can now call event handler and plugin methods from outside of the event handler, using `getEventHandler()` on an `API` instance, see [the docs for more info](https://docs.madelineproto.xyz/docs/PLUGINS.html#limitations)!

- Automatic static analysis of event handler code

Finally, all new bots and plugins will be automatically analyzed by MadelineProto, blocking execution if performance or security issues are detected!

Other features:
- Thanks to the many translation contributors @ https://weblate.madelineproto.xyz/, MadelineProto is now localized in Hebrew, Persian, Kurdish, Uzbek, Russian, French and Italian!
- Added simplified [sendMessage](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendmessage-int-string-peer-string-message-html-markdown-null-parsemode-null-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-nowebpage-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message), [sendDocument](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#senddocument-int-string-peer-message-media-danog-madelineproto-localfile-danog-madelineproto-remoteurl-danog-madelineproto-botapifileid-readablestream-file-message-media-danog-madelineproto-localfile-danog-madelineproto-remoteurl-danog-madelineproto-botapifileid-readablestream-null-thumb-null-string-caption-html-markdown-null-parsemode-null-callable-callback-null-string-filename-null-string-mimetype-null-int-ttl-null-bool-spoiler-false-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message), [sendPhoto](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendphoto-int-string-peer-message-media-danog-madelineproto-localfile-danog-madelineproto-remoteurl-danog-madelineproto-botapifileid-readablestream-file-string-caption-html-markdown-null-parsemode-null-callable-callback-null-string-filename-null-int-ttl-null-bool-spoiler-false-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message) methods that return abstract [Message](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/EventHandler/Message.html) objects with simplified properties and bound methods!
- You can now use `Tools::callFork` to fork a new green thread!
- You can now automatically pin messages broadcasted using `broadcastMessages`, `broadcastForwardMessages` by using the new `pin: true` parameter!
- You can now use `sendMessageToAdmins` to send messages to the bot's admin (the peers returned by `getReportPeers`).
- Added `wrapUpdate`, `wrapMessage`, `wrapMedia` methods to wrap low-level MTProto updates into an abstracted Message object with bound methods!
- The `waveform` attribute of `Voice` objects is now automatically encoded and decoded to an array of 100 integer values!
- Added a custom `PeerNotInDbException` class for "This peer is not present in the internal peer database" errors
- Added a `label` property to the Button class, directly indicating the button label (instead of manually fetching it as an array key).
- Added `isForum` method to check whether a given supergroup is a forum
- Added an `entitiesToHtml` method to convert a message and a set of Telegram entities to an HTML string!	
- You can now use `reportMemoryProfile()` to generate and send a `pprof` memory profile to all report peers to debug the causes of high memory usage.
- Added support for `pay`, `login_url`, `web_app` and `tg://user?id=` buttons in bot API syntax!
- Added a `getAdminIds` function that returns the IDs of the admin of the bot (equal to the peers returned by getReportPeers in the event handler).

Fixes:
- Fixed file uploads with ext-uv!
- Fixed file re-uploads!
- Improve background broadcasting with the broadcast API using a pre-defined list of `whitelist` IDs!
- Fixed a bug that caused updates to get paused if an exception is thrown during onStart.
- Broadcast IDs are now unique across multiple broadcasts, even if previous broadcasts already completed their ID will never be re-used.
- Now uploadMedia, sendMedia and upload can upload files from string buffers created using `ReadableBuffer`.
- Reduced memory usage during flood waits by tweaking config defaults.
- Reduced memory usage by clearing the min database automatically as needed.
- Automatically try caching all dialogs if a peer not found error is about to be thrown.
- Fixed some issues with pure phar installs.
- And many other performance improvements and bugfixes!
