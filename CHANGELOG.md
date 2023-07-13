MadelineProto was updated (8.0.0-beta100)!

Features:
- Thanks to the many translation contributors @ https://weblate.madelineproto.xyz/, MadelineProto is now localized in Hebrew, Persian, Kurdish, Uzbek, Russian, French and Italian!
- You can now use `Tools::callFork` to fork a new green thread!
- You can now automatically pin messages broadcasted using `broadcastMessages`, `broadcastForwardMessages` by using the new `pin: true` parameter!
- You can now use `sendMessageToAdmins` to send messages to the bot's admin (the peers returned by `getReportPeers`).
- Added `wrapUpdate`, `wrapMessage`, `wrapMedia`
- Added `Cron`
- Added plugins, filters, simple filters
- The `waveform` attribute of `Voice` objects is now automatically encoded and decoded to an array of 100 integer values!
- Added a custom PeerNotInDbException class for "This peer is not present in the internal peer database" errors
- Added a `label` property to the Button class, directly indicating the button label (instead of manually fetching it as an array key).
- Added `isForum` method to check whether a given supergroup is a forum
- Added `entitiesToHtml` method to convert a message and a set of Telegram entities to an HTML string!	
- You can now use `reportMemoryProfile()` to generate and send a `pprof` memory profile to all report peers to debug the causes of high memory usage.
- Added support for `pay`, `login_url`, `web_app` and `tg://user?id=` buttons in bot API syntax!
- Added a `getAdmin` function that returns the ID of the admin of the bot (which is equal to the first peer returned by getReportPeers in the event handler).
- getPlugin can now be used from IPC clients!
- `getReply`, `sendMessage`, `sendDocument`, `sendPhoto`, `reply`, `delete`

Fixes:
- Fixed file uploads with ext-uv!
- Fixed file re-uploads!
- Improve background broadcasting with the broadcast API using a pre-defined list of `whitelist` IDs!
- Fixed a bug that caused updates to get paused if an exception is thrown during onStart.
- Broadcast IDs are now unique across multiple broadcasts, even if previous broadcasts already completed their ID will never be re-used.
- Now uploadMedia, sendMedia and upload can upload files from string buffers created using `ReadableBuffer`.
- Reduce memory usage during flood waits by tweaking config defaults.
- Reduce memory usage by clearing the min database automatically as needed.
- Automatically try caching all dialogs if a peer not found error is about to be thrown
- Fix some issues with pure phar installs
- And many performance improvements and bugfixes!
