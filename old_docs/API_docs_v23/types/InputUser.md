---
title: InputUser
description: constructors and methods of type InputUser
---
## Type: InputUser  
[Back to types index](index.md)



You can directly provide the [Update](Update.md) or [Message](Message.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntaxes can also be used:

```
$InputUser = '@username'; // Username

$InputUser = 'me'; // The currently logged-in user

$InputUser = 44700; // bot API id (users)
$InputUser = -492772765; // bot API id (chats)
$InputUser = -10038575794; // bot API id (channels)

$InputUser = 'https://t.me/danogentili'; // t.me URLs
$InputUser = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

$InputUser = 'user#44700'; // tg-cli style id (users)
$InputUser = 'chat#492772765'; // tg-cli style id (chats)
$InputUser = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


### Possible values (constructors):

[inputUserEmpty](../constructors/inputUserEmpty.md)  

[inputUserSelf](../constructors/inputUserSelf.md)  

[inputUserContact](../constructors/inputUserContact.md)  

[inputUserForeign](../constructors/inputUserForeign.md)  



### Methods that return an object of this type (methods):



