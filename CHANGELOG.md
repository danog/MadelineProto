Introducing MadelineProto's biggest update yet, 8.0.0-beta100!

This version introduces [plugins »](https://docs.madelineproto.xyz/docs/PLUGINS.html), [bound methods »](https://docs.madelineproto.xyz/docs/UPDATES.html#bound-methods), [filters »](https://docs.madelineproto.xyz/docs/FILTERS.html), [a built-in cron system »](https://docs.madelineproto.xyz/docs/UPDATES.html#cron), [IPC support for the event handler »](https://docs.madelineproto.xyz/docs/UPDATES.html#persisting-data-and-ipc) and [automatic static analysis for event handler code »](https://docs.madelineproto.xyz/docs/UPDATES.html#automatic-static-analysis).

See the [following post](https://t.me/MadelineProto/630) for examples!

Other features:
- Thanks to the many translation contributors @ https://weblate.madelineproto.xyz/, MadelineProto is now localized in Hebrew, Persian, Kurdish, Uzbek, Russian, French and Italian!
- Added simplified [sendMessage](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendmessage-int-string-peer-string-message-parsemode-parsemode-danog-madelineproto-parsemode-text-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-nowebpage-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message), [sendDocument](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#senddocument-int-string-peer-message-media-localfile-remoteurl-botapifileid-readablestream-file-message-media-localfile-remoteurl-botapifileid-readablestream-null-thumb-null-string-caption-parsemode-parsemode-danog-madelineproto-parsemode-text-callable-callback-null-string-filename-null-string-mimetype-null-int-ttl-null-bool-spoiler-false-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message), [sendPhoto](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendphoto-int-string-peer-message-media-localfile-remoteurl-botapifileid-readablestream-file-string-caption-parsemode-parsemode-danog-madelineproto-parsemode-text-callable-callback-null-string-filename-null-int-ttl-null-bool-spoiler-false-int-null-replytomsgid-null-int-null-topmsgid-null-array-null-replymarkup-null-int-null-sendas-null-int-null-scheduledate-null-bool-silent-false-bool-noforwards-false-bool-background-false-bool-cleardraft-false-bool-updatestickersetsorder-false-danog-madelineproto-eventhandler-message) methods that return abstract [Message](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/EventHandler/Message.html) objects with simplified properties and bound methods!
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
- Added a new `ParseMode` enum!
- Added support for HTML lists in parseMode!
- Fixed parsing of markdown code blocks!

Breaking changes:
- Switched to a custom markdown parser with [bot API MarkdownV2](https://core.telegram.org/bots/api#markdownv2-style) syntax, which differs from the previous Markdown syntax supported by parsedown.
- Markdown text can't contain HTML anymore.

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
- Fixed splitting of HTML and markdown messages
- Fixed formatting of multiline markdown codeblocks
- And many other performance improvements and bugfixes!