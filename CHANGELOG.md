MadelineProto was updated (8.0.0-beta100)!

Features:
- Thanks to the many translation contributors @ https://weblate.madelineproto.xyz/, MadelineProto is now fully localized in Hebrew, Persian, Kurdish, Uzbek and Italian, with WIP translations in Russian and French!
- You can now use `Tools::callFork` to fork a new green thread!
- You can now automatically pin messages broadcasted using `broadcastMessages`, `broadcastForwardMessages` by using the new `pin: true` parameter!
- The `waveform` attribute of `Voice` objects is now automatically encoded and decoded to an array of 100 integer values!
- Added a custom PeerNotInDbException class for "This peer is not present in the internal peer database" errors
- Added a `label` property to the Button class, directly indicating the button label (instead of manually fetching it as an array key).
- Added `isForum` method to check whether a given supergroup is a forum
- Added `entitiesToHtml` method to convert a message and a set of Telegram entities to an HTML string!	
- Added `wrapUpdate`, `wrapMessage`, `wrapMedia`
- You can now use `reportMemoryProfile()` to generate and send a `pprof` memory profile to all report peers to debug the causes of high memory usage.
- Added support for `pay`, `login_url`, `web_app` and `tg://user?id=` buttons in bot API syntax!

Fixes:
- Fixed file uploads with ext-uv!
- Many performance improvements and bugfixes!
- Improve background broadcasting with the broadcast API using a pre-defined list of `whitelist` IDs!
- Broadcast IDs are now unique across multiple broadcasts, even if previous broadcasts already completed their ID will never be re-used.
- Now uploadMedia, sendMedia and upload can upload files from string buffers created using `ReadableBuffer`.
- Reduce memory usage during flood waits by tweaking config defaults.
- Reduce memory usage by clearing the min database automatically as needed.
- Automatically try caching all dialogs if a peer not found error is about to be thrown
