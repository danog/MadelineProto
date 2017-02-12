---
title: User
description: constructors and methods of type User
---
## Type: User  
[Back to types index](index.md)



The following syntaxes can also be used:

```
$messageActionHistoryClear = '@username'; // Username

$messageActionHistoryClear = 44700; // bot API id (users)
$messageActionHistoryClear = -492772765; // bot API id (chats)
$messageActionHistoryClear = -10038575794; // bot API id (channels)

$messageActionHistoryClear = 'user#44700'; // tg-cli style id (users)
$messageActionHistoryClear = 'chat#492772765'; // tg-cli style id (chats)
$messageActionHistoryClear = 'channel#38575794'; // tg-cli style id (channels)
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



