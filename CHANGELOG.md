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
* **Very important**, I wrote [documentation](https://docs.madelineproto.xyz/docs/LOGIN.html#getting-permission-to-use-the-telegram-api) on what to do if your account gets banned.  * Dropped support for PHP 5 and PHP 7.0: these versions of PHP have [officially reached their EOL](http://php.net/eol.php), so MadelineProto will not support them anymore.  
You should use MadelineProto with PHP 7.3 (or PHP 7.2; PHP 7.1 is supported but not recommended).  
* **Dropped support for get_updates**: it won't work properly on async, and I really recommend you stop using it
* You can now use the `@support` username in sendMessage and other methods to send messages to the support user!
* Now MadelineProto will automatically try to get the access hash of users not present in the internal peer database (this should reduce errors)!  
* If any file cannot be downloaded to due issues with the tg media server that is hosting it, it will be automatically sent to the `@support` user ([settings](https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsdownloadreport_broken_media)).  
* Documented the [MyTelegramOrgWrapper](https://docs.madelineproto.xyz/docs/LOGIN.html#api-id) API, that can be used to login programmaticaly to the [my.telegram.org](https://my.telegram.org management page).  
* Added an [update_2fa](https://docs.madelineproto.xyz/update_2fa.html) method to update the login password
* Added a [get_full_dialogs](https://docs.madelineproto.xyz/docs/DIALOGS.html#get_full_dialogs-now-fully-async) method to get a full list of all chats you’re member of, including **dialog info** (such as the pinned/last message ID, unread count, tag count, notification settings and message drafts).  
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
* When running from web, MadelineProto will also automatically enable logging of **PHP errors** (not MadelineProto logs) to `MadelineProto.log`, located in the same directory as the script that loaded MadelineProto.  


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

While thinking of an easy way I could implement the new update system, **I had an inspiration**:  

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
With **MadelineProto 4.0**, each update is handled in **parallel** using a separate **thread**, and everything is done in **parallel** (even on restricted webhosts, perfect for creating **file downloader bots**!).  

When I say **thread**, I actually mean **green thread** ([wikipedia](https://en.wikipedia.org/wiki/Green_threads)), often called **strand**.  
**Strands** are behave exactly like normal **threads**, except that they're implemented in user-space, and they're much **faster**, **more reliable**, and **do not suffer** from synchronization issues present with normal threads.  

Each update you receive using the **event handler** or the **callback handler** is managed in parallel in separate **green threads**: the **only thing** you have to do to enable **async** with **green threads** is add a `yield` before calling MadelineProto methods.  

[Full async documentation with examples](https://docs.madelineproto.xyz/docs/ASYNC.html).  

If your code still relies on the **old synchronous behaviour**, __don't worry__, there is backward compatibility.  
However, old synchronous behaviour is deprecated, and I **highly recommend** you switch to async, due to the **huge performance** and **parallelism benefits**.  

***
To implement async, I wrote loads of new async APIs in MadelineProto, as you may have seen above.  
I used the **awesome** [amphp](https://amphp.org) async framework as base, on which to build the new MadelineProto APIs.  
I heavily modified amphp coroutines and wrapped [all of the AMPHP event loop methods](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-and-amphp-async-apis) to add native support for yielding generators.  

I have also wrapped multiple amphp async libraries for ease of use and compatiblity with MadelineProto settings:  
* [MadelineProto artax HTTP client](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-artax-http-client):  
I wrapped the amphp [artax](https://amphp.org/artax) HTTP library for greater security, and to add support for socks5 and HTTP **proxies**: the proxy settings are automatically extracted from MadelineProto settings.  
Soon, MadelineProto's artax will support DNS over HTTPS by default.  

I also provided a wrapper version of `file_get_contents`:
```php
$file = yield $MadelineProto->file_get_contents('https://url');
```

You can use this library to asynchronously download files from the web.  

* ALL internal MadelineProto methods were converted to async automatically using [an automatic script](https://github.com/danog/MadelineProto/blob/master/asyncify.php): with some changes and conversion to use [php-parser](https://github.com/nikic/PHP-Parser), it can be used to automatically asyncify **any** script (will implement in a future version of MadelineProto).  
* I created a custom async API to asynchronously construct objects:  
This allows you to create multiple instances of MadelineProto **asynchronously**, because each instantiation will be done asynchronously.  

```php
$com = new \danog\MadelineProto\CombinedAPI('combined_session.madeline', ['bot.madeline' => $settings, 'user.madeline' => $settings, 'user2.madeline' => $settings]);
$com->async(true);
$com->loop(function () use ($com) {
    $res = [];
    foreach (['bot.madeline', 'user.madeline', 'user2.madeline'] as $session) {
        $res []= $com->instances[$session]->start();
    }
    yield $com->all($res);
    yield $com->setEventHandler('\EventHandler');
}
$com->loop();
```

Internally, the combined event handler does `new \danog\MadelineProto\API`, but it isn't blocking:  
this means that later, when I combine all the async `start()`s into one array and yield it using the [async combinator function](https://docs.madelineproto.xyz/docs/ASYNC.html#combining-async-operations), initialization of sessions is done in parallel, and not one after the other.  


* I wrapped amphp's helper functions, and created some more:  

The `all` function you saw above is one of the many combinator functions that can be used to execute multiple async operations simultaneously and wait for the result of all of them.  
Each method has different error handling techniques, see the [amphp docs](https://amphp.org/amp/promises/combinators).  
Note that if you just take the result of these methods without yielding it, you can use it as a normal promise/generator.  

**Note**: this is **not** the recommended method to make multiple method calls on the same instance of MadelineProto; use this only for non-API methods like `start()`; for API methods, use [multiple async](https://docs.madelineproto.xyz/docs/ASYNC.html#multiple-async).  

```
$promise1 = $MadelineProto->messages->sendMessage(...);
$promise2 = $MadelineProto->messages->sendMessage(...);
// $promise3 = ...;

// Equivalent to Amp\Promise\all(), but works with generators, too
$results = yield $MadelineProto->all([$promise1, $promise2, $generator3]);

// Equivalent to Amp\Promise\first(), but works with generators, too
$results = yield $MadelineProto->first([$promise1, $promise2, $generator3]);

// Equivalent to Amp\Promise\any(), but works with generators, too
$results = yield $MadelineProto->any([$promise1, $promise2, $generator3]);

// Equivalent to Amp\Promise\some(), but works with generators, too
$results = yield $MadelineProto->some([$promise1, $promise2, $generator3]);
```

* Handling timeouts

These methods can be used to wait for a certain amount of time for a result, and then throw an `Amp\TimeoutException` or simply continue execution if no result was obtained.  

```
// Waits for the result for 2 seconds and then throws an \Amp\TimeoutException
$result = yield $MadelineProto->timeout($promise, 2)

// Waits for the result for 2 seconds, returns the result or null (which is the result of sleep())
$result = yield $MadelineProto->first([$promise, $MadelineProto->sleep(2)]);
```


* Async forking (does async green thread forking)

Useful if you need to start a process in the background and you want throwed exceptions to surface up.  
These exceptions will exit the event loop, turning off the script unless you wrap `$MadelineProto->loop()` with a try-catch.  
Use it when you do not need the result of a method (see [ignored async](https://docs.madelineproto.xyz/docs/ASYNC.html#ignored-async)), but you want eventual errors to crash the script.  
Otherwise, just use the method without yield.  

```php
// Exceptions will surface out of the event loop()
$MadelineProto->callFork($MadelineProto->messages->sendMessage([...]));
// Exceptions will be ignored
$MadelineProto->messages->sendMessage([...]);

// Like the first one, but the call will be deferred to the next event loop tick
$MadelineProto->callForkDefer($MadelineProto->messages->sendMessage([...]));
```

Ignoring exceptions is usually not good practice, so it's best to wrap the method you're calling in a closure with a try-catch with some error handling code inside of it, calling it right after that and passing it to callFork:

```php
$MadelineProto->callFork((function () use ($MadelineProto) {
    try {
        $MadelineProto->messages->sendMessage([...])
    } catch (\Exception $e) {
        // Handle by logging and stuff
    }
})());
```

* I also created some wrapper functions to work asynchronously with console/browser output

Async sleep:
```php
yield $MadelineProto->sleep(3);
```

Async readline:
```php
$res = yield $MadelineProto->readLine('Optional prompt');
```

* Logging in MadelineProto is now completely asynchronous and easier:
```php
$MadelineProto->logger("Message");
```

No need to yield here, because the logging must be done in background.  

* Simultaneous method calls
```php
yield $MadelineProto->messages->sendMessage([
    'multiple' => true,
    ['peer' => '@danogentili', 'message' => 'hi'],
    ['peer' => '@apony', 'message' => 'hi']
]);
```

This is the preferred way of combining multiple method calls: this way, the MadelineProto async WriteLoop will combine **all method calls in one container**, making everything **WAY faster**.  
The result of this will be an array of results, whose type is determined by the original return type of the method (see [API docs](https://docs.madelineproto.xyz/API_docs)).  


The order of method calls can be guaranteed (server-side, not by MadelineProto) by using [call queues](https://docs.madelineproto.xyz/docs/USING_METHODS.html#queues).  

* Exceptions:  

**NOTE**: Due to the async nature of MadelineProto 4.0, sometimes the exception that is thrown and logged may not be the actual exception that caused the crash of the script.  
To let me properly debug the issue, when reporting issues you also have to provide [**full logs**](https://docs.madelineproto.xyz/docs/LOGGING.html).  

* Finally, [async loops](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis).  

MadelineProto provides some very useful async loop APIs, for executing operations periodically or on demand.  

They are the perfect solution for implementing **async cron** loops, signal threads and much more!  
I'll just link you all to the [documentation](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis): it has full examples for each and every async API (you can also check out the [code](https://github.com/danog/MadelineProto/tree/master/src/danog/MadelineProto/Loop), it's full of PHPDOC comments).  

***

Writing MadelineProto async, I **really enjoyed** working with the AMPHP framework: it is **very fast**, has [multiple packages](https://amphp.org/packages) to work asynchronously with HTTP requests, websockets, databases (MySQL, redis, postgres, DNS, sockets and [much more](https://github.com/amphp/))!  

I chose AMPHP instead of the more famous ReactPHP due to its **speed**, **rich set of libraries** and **extreme ease of use**.  

Working with its devs is also nice; I already contributed to the amphp's libraries with some improvements and bugfixes (I will soon also implement a DNS over HTTPS client for AMPHP, to implement in [MadelineProto's artax](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-artax-http-client)), and I invite you to do the same!  

Even if you can't contribute to AMPHP, you can still use it: as I mentioned above, there are MANY libraries to work asynchronously with files, databases, DNS, HTTP; there's even an async windows registry client, used by the DNS client to fetch default DNS servers on windows!  

When you use MadelineProto async, you **have** to also use an amphp async database client, **artax** instead of curl and guzzle, and so on: otherwise, the speed of MadelineProto async may be reduced by blocking behaviour of your code.  

*** 

In case you missed it, [quick reminder that MadelineProto now supports MTProxy](https://docs.madelineproto.xyz/docs/PROXY.html)!  

*** 

MadelineProto started as a hobby project, back when I knew nothing about cryptography, telegram's APIs, async or programming standards:

before, MadelineProto was created piece by piece as a single monolothic class composed of multiple traits; 
now, MadelineProto is composed of **multiple modular APIs** that are **well-structured**, **heavily commented**, **wrappable**, **extendable** in any possible and immaginable way.  

I am really happy with how MadelineProto async turned out.  
I had an absolute blast working on this update, and implementing async really opened a **whole sea** of possible innovations and features I can implement in MadelineProto now:

* Async file upload by url (1.5gb)
* Get direct download url of any file (1.5gb)
* TON (this is actually going to be a lot of fun)
* group calls (the php-libtgvoip APIs are actually ready, I just need to wrap them in php-libtgvoip)
* video calls (~)
* native calls:
With MadelineProto async, I can finally properly implement **native async phone calls in PHP**:  
this will allow handling **phone calls on webhosts**!
I already have some code I created a year ago for this backed up in a private gitlab repo :)
* async iterators:
I've been thinking of using AMPHP's [async iterator API](https://amphp.org/amp/iterators/) (after some modding obviously) to create async iterators for easily iterating over the messages of a group, and for doing other operations that would normally require using offsets:  

```php
foreach ($MadelineProto->getMessages('@group') as $message) {

}
```

This shouldn't be too hard to implement, and with a proper (maybe separate OOP) API, it's going to be fun to make and use.  

* `snake_case` => `CamelCase` conversion for all API methods:  
Previously, MadelineProto's custom API methods (`get_info`, `download_to_dir`) used `snake_case`, which contrasted with the Telegram API methods (`sendMessage`), and is against PHP coding standards.  
Soon, I plan to update MadelineProto's docs to only use `CamelCase` for method names.  

The old method name will still be available after that; right now, you can already use both naming conventions for **all** MadelineProto methods:  

```php
$MadelineProto->get_pwr_chat('user'); // OK!
$MadelineProto->getPwrChat('user'); // OK!
```

However, I recommend you now use the `CamelCase` version of methods.  

* ArrayAccess on promises (to be able to do `yield $method()['result']` instead of `(yield $method)['result']`)
* An `openChat` method, inspired by tdlib, to enable fetching updates from groups **you aren't a member of**
* Add support for Telegram passport in 2FA and write some wrapper APIs
* Write some simplified APIs for takeout (can be implemented using async iterators)
* #MadelineProtoForNode async and lua async (the second can already be done now, the first is also pretty easy now that async is here :)))))
* DNS over HTTPS everywhere
* Parallelize some methods like the download method, or getPwrChat (upload is already fully parallelized)
* Get sponsor of MTProxies
* Optional max_id and min_id params in methods
* #phase1 🇮🇷🇷🇺


***

To use MadelineProto 4.0 w/ async, you have to load the **latest version** of MadelineProto from the **master** branch by loading it through composer (`dev-master`) or with madeline.php:  
```php
<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
define('MADELINE_BRANCH', '');
include 'madeline.php';
```

In a few weeks I will set MadelineProto 4.0 **as default** with `madeline.php`: in the meantime, I **do not provide support** for the old version.  

***



* Write some docs for the useful get mime funcs
* Figure out web file proxying (might be interesting)
* Re-enable gzip in the write loop
* no defer logs
* startedLoop docs

tell about restart
tell about madeline.php loading in the same dire

remind about using the define

Handle auth_restart
splitting
