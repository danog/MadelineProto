---
title: InputPeer
description: constructors and methods of type InputPeer
---
## Type: InputPeer  
[Back to types index](index.md)



You can directly provide the [Update](Update.md) or [Message](Message.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntaxes can also be used:

```
$InputPeer = '@username'; // Username

$InputPeer = 'me'; // The currently logged-in user

$InputPeer = 44700; // bot API id (users)
$InputPeer = -492772765; // bot API id (chats)
$InputPeer = -10038575794; // bot API id (channels)

$InputPeer = 'https://t.me/danogentili'; // t.me URLs
$InputPeer = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

$InputPeer = 'user#44700'; // tg-cli style id (users)
$InputPeer = 'chat#492772765'; // tg-cli style id (chats)
$InputPeer = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


### Possible values (constructors):

[inputPeerEmpty](../constructors/inputPeerEmpty.md)  

[inputPeerSelf](../constructors/inputPeerSelf.md)  

[inputPeerChat](../constructors/inputPeerChat.md)  

[inputPeerUser](../constructors/inputPeerUser.md)  



### Methods that return an object of this type (methods):



