# Changelog

## 4.0.0 Full async

**Fully asynchronous MadelineProto!**

MadelineProto now features async, for **incredible speed improvements**, and **parallel processing**.
Powered by [amphp](https://amphp.org), MadelineProto wraps the AMPHP APIs to provide a simpler generator-based async API.  

What exactly __is__ **async**, you may ask, and how is it better than **threading** or **multiprocessing**?  
Async is a relatively new programming pattern that allows you to easily write **non-blocking** code **as if you were using standard** blocking functions, all without the need for complex message exchange systems and synchronization handling for threaded programs, that only add overhead and complexity to your programs, making everything slower and error-prone.  

More simply put: with **MadelineProto 4.0**, each update is handled in **parallel** using a separate **thread**, and everything is done in **parallel** (even on restricted webhosts!).  

To enable async, you have to do two simple things: 
1) [Load the latest version of MadelineProto](https://docs.madelineproto.xyz/docs/ASYNC.html#loading-the-latest-version-of-madelineproto)
2) [`yield` your method calls](https://docs.madelineproto.xyz/docs/ASYNC.html#enabling-the-madelineproto-async-api).  


That's it!  
**No need** to set up thread pools (that don't even work in PHP), use synchronization primitives, and so on...
Just `yield $MadelineProto->messages->sendMessage` instead of `$MadelineProto->messages->sendMessage`.  

~~~~

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
If you want all communcations with telegram to be **double** or **triple**-encrypted using HTTPS+MTProto+obfuscation, you can enable the `wss` transport.  

(the websocket transport may or may not also be used in future to implement MadelineProto in the browser with webassembly ;)  

The new [proxy API](https://docs.madelineproto.xyz/docs/PROXY.html) is also based on the new stream API, and now supports **MTProxies**!  
You can still use the socks5 and HTTP transports if you want.  
[Writing your custom proxies](https://docs.madelineproto.xyz/docs/PROXY.html#build-your-proxy) now is now a LOT easier, thanks to the neat structure of the stream APIs and the abundant PHPDOC comments.  

~~~

Streams and proxies aren't the only things that have changed in this release:  
I have written a **unique** socket message handler API based on [MadelineProto's loop API](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis):  and greatly simplifies 


Let's elaborate on this: when I say **thread**, I actually mean **green thread**, often called **strand**.  
**Strands** are behave exactly like normal **threads**, except that they're implemented in user-space, and they're much **faster**, **more reliable**, and **do not suffer** from synchronization issues present with normal threads.  


* Fully rewritten connection stack, with support for websockets, stuff
* updates
* simultaneous method calls
* new TL callback system
* added support for wallpapers
* Improved message splitting algorithm: performance improvements, and it will now notify you via the logs if there are too many entities in the logs, or if the entities are too long.  
* Improved get_self method.  
* reference database
* Rewritten proxy stack
* magic sleep
* get_full_dialogs
* new APIfactory
* sendmessage with secret messages
* automatic secret chat file upload
* 2fa+++++
* improved callfork
* split acks
* new logging
* TL callabck
* channel state
* logger
* async construct
* clean up repo, update dependencies and remove curl dependency
* new phone call config
* updated php-libtgvoip
* improved madeline.php loader
* async constructor
* removed old serialization 
* rewrote combined update handler (async)
* modify amphp
* async logging
* phpdoc
* @support
* even without access hash for bots
* async HTTP requests internally
* custom HTTP client with DoH
* no more php 5
* reset PTS to 0
* arrayaccess on args
* increased flood wait

Things to expect in the next releases:
docs for get mime funcs
docs for update_2fa
docs for ResponseException
docs for PTSException
Document async apis
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

telegram passport