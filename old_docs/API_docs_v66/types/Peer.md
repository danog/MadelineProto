---
title: Peer
description: constructors and methods of type Peer
---
## Type: Peer  
[Back to types index](index.md)



You can directly provide the [Update](Update.md) or [Message](Message.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntaxes can also be used:

```
$Peer = '@username'; // Username

$Peer = 'me'; // The currently logged-in user

$Peer = 44700; // bot API id (users)
$Peer = -492772765; // bot API id (chats)
$Peer = -10038575794; // bot API id (channels)

$Peer = 'https://t.me/danogentili'; // t.me URLs
$Peer = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

$Peer = 'user#44700'; // tg-cli style id (users)
$Peer = 'chat#492772765'; // tg-cli style id (chats)
$Peer = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


### Possible values (constructors):

[peerUser](../constructors/peerUser.md)  

[peerChat](../constructors/peerChat.md)  

[peerChannel](../constructors/peerChannel.md)  



### Methods that return an object of this type (methods):



