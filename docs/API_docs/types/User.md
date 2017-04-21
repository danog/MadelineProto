---
title: User
description: constructors and methods of type User
---
## Type: User  
[Back to types index](index.md)



The following syntaxes can also be used:

```
$User = '@username'; // Username

$User = 44700; // bot API id (users)
$User = -492772765; // bot API id (chats)
$User = -10038575794; // bot API id (channels)

$User = 'user#44700'; // tg-cli style id (users)
$User = 'chat#492772765'; // tg-cli style id (chats)
$User = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


### Possible values (constructors):

[userEmpty](../constructors/userEmpty.md)  

[user](../constructors/user.md)  



### Methods that return an object of this type (methods):

[$MadelineProto->account->updateProfile](../methods/account_updateProfile.md)  

[$MadelineProto->account->updateUsername](../methods/account_updateUsername.md)  

[$MadelineProto->account->changePhone](../methods/account_changePhone.md)  

[$MadelineProto->users->getUsers](../methods/users_getUsers.md)  

[$MadelineProto->contacts->importCard](../methods/contacts_importCard.md)  



