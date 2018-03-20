---
title: InputChannel
description: constructors and methods of type InputChannel
---
## Type: InputChannel  
[Back to types index](index.md)



You can directly provide the [Update](Update.md) or [Message](Message.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntaxes can also be used:

```
$InputChannel = '@username'; // Username

$InputChannel = 'me'; // The currently logged-in user

$InputChannel = 44700; // bot API id (users)
$InputChannel = -492772765; // bot API id (chats)
$InputChannel = -10038575794; // bot API id (channels)

$InputChannel = 'https://t.me/danogentili'; // t.me URLs
$InputChannel = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

$InputChannel = 'user#44700'; // tg-cli style id (users)
$InputChannel = 'chat#492772765'; // tg-cli style id (chats)
$InputChannel = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


### Possible values (constructors):

[inputChannelEmpty](../constructors/inputChannelEmpty.md)  

[inputChannel](../constructors/inputChannel.md)  



### Methods that return an object of this type (methods):



