---
title: Chat
description: constructors and methods of type Chat
---
## Type: Chat  
[Back to types index](index.md)



You can directly provide the [Update](Update.md) or [Message](Message.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntaxes can also be used:

```
$Chat = '@username'; // Username

$Chat = 'me'; // The currently logged-in user

$Chat = 44700; // bot API id (users)
$Chat = -492772765; // bot API id (chats)
$Chat = -10038575794; // bot API id (channels)

$Chat = 'https://t.me/danogentili'; // t.me URLs
$Chat = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

$Chat = 'user#44700'; // tg-cli style id (users)
$Chat = 'chat#492772765'; // tg-cli style id (chats)
$Chat = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


### Possible values (constructors):

[chatEmpty](../constructors/chatEmpty.md)  

[chat](../constructors/chat.md)  

[chatForbidden](../constructors/chatForbidden.md)  

[channel](../constructors/channel.md)  

[channelForbidden](../constructors/channelForbidden.md)  



### Methods that return an object of this type (methods):



