---
title: User
description: constructors and methods of type User
---
## Type: User  
[Back to types index](index.md)



The following syntaxes can also be used:

```
$messages_highScores = '@username'; // Username

$messages_highScores = 44700; // bot API id (users)
$messages_highScores = -492772765; // bot API id (chats)
$messages_highScores = -10038575794; // bot API id (channels)

$messages_highScores = 'user#44700'; // tg-cli style id (users)
$messages_highScores = 'chat#492772765'; // tg-cli style id (chats)
$messages_highScores = 'channel#38575794'; // tg-cli style id (channels)
```


### Possible values (constructors):

[userEmpty](../constructors/userEmpty.md)  

[user](../constructors/user.md)  



### Methods that return an object of this type (methods):

[$MadelineProto->account->updateProfile](../methods/account_updateProfile.md)  

[$MadelineProto->users->getUsers](../methods/users_getUsers.md)  

[$MadelineProto->contacts->importCard](../methods/contacts_importCard.md)  

[$MadelineProto->account->updateUsername](../methods/account_updateUsername.md)  

[$MadelineProto->account->changePhone](../methods/account_changePhone.md)  



