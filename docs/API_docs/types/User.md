---
title: User
description: constructors and methods of type User
---
## Type: User  
[Back to types index](index.md)



The following syntaxes can also be used:

```
$phoneCallDiscardReasonBusy = '@username'; // Username

$phoneCallDiscardReasonBusy = 44700; // bot API id (users)
$phoneCallDiscardReasonBusy = -492772765; // bot API id (chats)
$phoneCallDiscardReasonBusy = -10038575794; // bot API id (channels)

$phoneCallDiscardReasonBusy = 'user#44700'; // tg-cli style id (users)
$phoneCallDiscardReasonBusy = 'chat#492772765'; // tg-cli style id (chats)
$phoneCallDiscardReasonBusy = 'channel#38575794'; // tg-cli style id (channels)
```


### Possible values (constructors):

[userEmpty](../constructors/userEmpty.md)  

[user](../constructors/user.md)  



### Methods that return an object of this type (methods):

[$MadelineProto->account->updateProfile](../methods/account_updateProfile.md)  

[$MadelineProto->account->updateUsername](../methods/account_updateUsername.md)  

[$MadelineProto->account->changePhone](../methods/account_changePhone.md)  

[$MadelineProto->users->getUsers](../methods/users_getUsers.md)  

[$MadelineProto->contacts->importCard](../methods/contacts_importCard.md)  



