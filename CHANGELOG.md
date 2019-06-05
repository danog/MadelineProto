# Changelog

## 4.0.0 Full async

**Fully asynchronous MadelineProto!**

MadelineProto now features async, for **incredible speed improvements**, and **parallel processing**.
Powered by [amphp](https://amphp.org), MadelineProto wraps the AMPHP APIs to provide a simpler generator-based async API.  

What exactly __is__ **async**, you may ask, and how is it better than **threading** or **multiprocessing**?  
Async is a relatively new programming pattern that allows you to easily write **non-blocking** code **as if you were using standard** blocking functions, all without the need for complex message exchange systems and synchronization handling for threaded programs, that only add overhead and complexity to your programs, making everything slower and error-prone.  

More simply put: with **MadelineProto 4.0**, each update is handled in **parallel** using a separate **thread**, and everything is done in **parallel** (even on restricted webhosts, perfect for creating **file downloader bots**!).  

To enable async, you have to do two simple things: 
1) [Load the latest version of MadelineProto](https://docs.madelineproto.xyz/docs/ASYNC.html#loading-the-latest-version-of-madelineproto)
2) [`yield` your method calls](https://docs.madelineproto.xyz/docs/ASYNC.html#enabling-the-madelineproto-async-api).  


That's it!  
**No need** to set up thread pools (that don't even work in PHP), use synchronization primitives, and so on...
Just `yield $MadelineProto->messages->sendMessage` instead of `$MadelineProto->messages->sendMessage`.  

***

And now, on to the **API changes**:
* First of all, we've got several bucketloads of telegram API changes, that can be viewed in the first posts.
* Dropped support for PHP 5 and PHP 7.0: these versions of PHP have [officially reached their EOL](http://php.net/eol.php), so MadelineProto will not support them anymore.  
You can use MadelineProto with PHP 7.3 (or PHP 7.2, PHP 7.1 is supported but not recommended).  
* **Dropped support for get_updates**: it won't work properly on async, and I really recommend you stop using it
* You can now use the `@support` username in sendMessage and other methods to send messages to the support user!
* Now MadelineProto will automatically try to get the access hash of users not present in the internal peer database (this should reduce errors)!  
* If any file cannot be downloaded to due issues with the tg media server that is hosting it, it will be automatically sent to the `@support` user ([settings](https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsdownloadreport_broken_media)).  
* Documented the [MyTelegramOrgWrapper](https://docs.madelineproto.xyz/docs/LOGIN.html#api-id) API, that can be used to login programmaticaly to the [my.telegram.org](https://my.telegram.org management page).  
* Added an [update_2fa](https://docs.madelineproto.xyz/update_2fa.html) method to update the login password
* Added a [get_full_dialogs](https://docs.madelineproto.xyz/docs/DIALOGS.html#get_full_dialogs-now-fully-async) method to get a full list of all chats you’re member of, including dialog info (such as the pinned/last message ID, unread count, tag count, notification settings and message drafts).  
* [Added support for automatic file uploads by name in secret chats (as with normal chats); you can also now send secret chat messages using the sendMessage method as if it were a normal chat](https://github.com/danog/MadelineProto/blob/master/secret_bot.php)
* Added a [resetUpdateState](https://docs.madelineproto.xyz/docs/UPDATES.html#fetch-all-updates-from-the-beginning) method to reset the update state and fetch ALL updates from the beginning
* Improved chat message splitting algorithm (if the message you're trying to send is too long): performance improvements, and it will now notify you via the logs if there are too many entities in the logs, or if the entities are too long.  
* Improved the get_self method.  
* [Added a __magic_sleep](https://docs.madelineproto.xyz/docs/UPDATES.html#async-combined-event-driven) substitute for `__sleep` in the `CombinedEventHandler`
* Removed all dependencies to `curl`: now all HTTP requests are made asynchronously using a custom version of [artax](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-artax-http-client) (more on that later).  
* Updated [php-libtgvoip](https://voip.madelineproto.xyz) and introduced a [common API](https://docs.madelineproto.xyz/docs/CALLS.html#changing-audio-quality) for changing phone call settings
* Improved the `madeline.php` loader
* Removed the old serialization APIs: now serialization is done automatically by MadelineProto!
* Increased the default flood wait limit to 10 minutes, since with async waiting for the flood wait isn't blocking anymore


***

Naturally, async is not the only feature present in MadelineProto 4.0: to implement async in MadelineProto, I rewrote the **entire codebase two times** over the course of the last six months, as shown in the diff:  
```
~$ git diff --stat old master
 187 files changed, 28692 insertions(+), 12288 deletions(-)
```

- I **completely refactored** the connection stack:
I threw out of the window my custom OOP wrapper for vanilla PHP sockets and created a brand new OOP connection stack.  
The new connection stack uses a brand new incremental buffered async socket wrapper [Stream API](https://github.com/danog/MadelineProto/tree/master/src/danog/MadelineProto/Stream), that greatly simplifies work with layered protocols like MTProto.  

Each MTProto packet is passed through a Stream layer, each one taking care of one part of the MTProto envelope, finally sending off everything to telegram using a simple AMPHP socket.  

The simplified async buffered implementation of AMPHP sockets I created allowed me to easily add support for ALL **MTProto protocols**, including a few I haven't implemented before like the padded protocol.  

Obfuscation is now handled via a separate setting, and can be enabled for **all** transports, not just `tcp_abridged` (it can be used to prevent ISP blocks in combination with the padded protocol, for example).  

I also added support for different **MTProto transports**, including the brand new **websocket transport** and **secure websocket transport (HTTPS)**, until now only implemented in tdlib!  
If you want all communcations with telegram to be **triple**-encrypted using HTTPS+MTProto+obfuscation, you can enable the `wss` transport (the third layer of encryption w/ obfuscation will be enabled automatically).  
Note: the **websocket HTTPS MTProto transport** is different from the **HTTPS MTProto protocol** (both are supported by MadelineProto).  
The **websocket HTTPS MTProto transport** is more reliable and faster than the **HTTPS MTProto protocol**, since MadelineProto does not have to handle HTTP long polls.  

(the websocket transport may or may not also be used in future to implement MadelineProto in the browser with webassembly ;)  

The new [proxy API](https://docs.madelineproto.xyz/docs/PROXY.html) is also based on the new stream API, and now supports **MTProxies**!  
You can still use the socks5 and HTTP transports if you want.  
[Writing your custom proxies](https://docs.madelineproto.xyz/docs/PROXY.html#build-your-proxy) now is now a LOT easier, thanks to the neat structure of the stream APIs and the abundant PHPDOC comments.  

***

Streams and proxies aren't the only things that have been completely rewritten in this release:  
I have written a **unique** socket message handler API based on [MadelineProto's loop API](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis), and guarantees **maximum reliability** while communicating with telegram.  
By the way, the new async background loop API can also be used by **you**! It is perfect for repeating tasks in a cron-like manner, running multiple resumable event loops and much more! More on that later.  

The new **message handler loops** run in the background, and guarantee that every single method call you make gets delivered to telegram.  
**Two signal loops** running in two separate green threads take care of writing and reading data from the socket asynchronously.  
A **third signal loop** uses state request messages to make sure that the method calls were received by telegram, and queries replies/resend method calls if something's wrong.  
A **fourth signal loop** takes care of HTTP long polling.  

This guarantees maximum stability even if telegram's having server issues.  
The write loop also greatly reduces overhead, increasing performances by automatically wrapping in containers multiple method calls: this is especially useful when making multiple method calls simultaneously with async (more on that later).  

The update state is now stored using a custom `UpdatesState` API, that will simplify backup to a DBMS backend later on :).  

***

Possibly the most __exciting__ thing to work on in this version of MadelineProto was the new **update management system**: I whipped it up in merely two days a few weeks ago, and it has **absolutely improved** the overall reliability of MadelineProto.  
Huge thanks to Aliaksei Levin, the developer of tdlib, for explaining to me how exactly does the MTProto update API work: he saved me a lot of time, and was really nice <3<3<3.  

While thinking of an easy way I could implement the new update system, I had an inspiration:  

I created a **unique**, **reliable**, **fast** and **extremely simple** update handling system based on [MadelineProto's loop API](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis), **not present in any** other MTProto client, **not even tdlib**.  

Instead of messing around with various synchronization locks, checks and cluttered update receivers, **I implemented three simple feed loops**.  
Updates are fed to the first update loop, where some simple duplicate/hole checks are done.
Updates are then fed to a second update loop, where secondary duplicate/hole checks are done.  
A third loop type takes care of eventual holes and periodically fetches updates for all supergroups, channels.  

All of this is implemented without **any** kind of additional synchronization or locking due to the nature of the **MadelineProto loop APIs**, with **1%** of the complexity of the official clients (which means less bugs and more pony time for me).  


***

Even if most of MadelineProto's logic is now concentrated in the new **loop** and **stream** APIs, some parts like the TL parser are still there, albeit with many changes.  
For example: now the TL parser is fully asynchronous (that may sound a bit weird to some of you, but for dynamic TL parsers, this greatly increases performances and allows parallelism).  

The TL deserializer now uses yet another well-structured API called the **TLCallback API** to automatically populate internal databases directly on deserialization: again, this paradigm greatly **reduces complexity**, **increases performance** and is **absolutely unique** to MadelineProto; no other MTProto client has it, not even official clients.  

One of the **completely new** modules that I created for MadelineProto async is the **file reference database**: a **very complex** module, required to handle files in the newer versions of the Telegram API.  
It makes use of the **TLCallback** API to map files to their origins, to be able to refetch them at any given time when the file reference expires.  

Another new module I've implemented is the **PasswordCalculator**, that is used to calculate the new password hashes for the 2FA login, __really cool__.  

I've also rewritten the **APIFactory**, the abstraction layer that stands between you and MadelineProto when you do `$MadelineProto->method()`: it is now fully async, and MUCH faster thanks to a new cached method mapping system.  
The same cached method mapping system is also used for the **event handler**, which means that now the **event handler is the fastest update management method**.  


***

And now, let's elaborate on async:  
With **MadelineProto 4.0**, each update is handled in **parallel** using a separate **thread**, and everything is done in **parallel** (even on restricted webhosts!).  

When I say **thread**, I actually mean **green thread** ([wikipedia](https://en.wikipedia.org/wiki/Green_threads)), often called **strand**.  
**Strands** are behave exactly like normal **threads**, except that they're implemented in user-space, and they're much **faster**, **more reliable**, and **do not suffer** from synchronization issues present with normal threads.  

In


* modify amphp
* phpdoc
* custom HTTP client with DoH
* Simultaneous method calls
* improved callfork
* new logging
* async construct
* async readline
* async filegetco

Things to expect in the next releases:
docs for get mime funcs
docs for HTML parser (div to avoid escaping)
docs for update_2fa
optional max_id and min_id
async iterators
Method name changes
#MadelineProtoForNode async
lua async
improved get_pwr_chat
gzip
no defer logs
recover@tg docs
startedLoop docs

no error setting, madelineproto does that for you


do not use manual
tell about madeline.php loading in the same dire
arrayaccess on promises
get sponsor of 
ton
video calls
group calls
native calls
dnssec
mytelegramorg docs
web files
telegram passport