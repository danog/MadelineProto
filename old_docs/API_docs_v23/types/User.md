---
title: User
description: constructors and methods of type User
---
## Type: User  
[Back to types index](index.md)



The following syntaxes can also be used:

```
$disabledFeature = '@username'; // Username

$disabledFeature = 44700; // bot API id (users)
$disabledFeature = -492772765; // bot API id (chats)
$disabledFeature = -10038575794; // bot API id (channels)

$disabledFeature = 'user#44700'; // tg-cli style id (users)
$disabledFeature = 'chat#492772765'; // tg-cli style id (chats)
$disabledFeature = 'channel#38575794'; // tg-cli style id (channels)
```


### Possible values (constructors):

[userEmpty](../constructors/userEmpty.md)  

[userSelf](../constructors/userSelf.md)  

[userContact](../constructors/userContact.md)  

[userRequest](../constructors/userRequest.md)  

[userForeign](../constructors/userForeign.md)  

[userDeleted](../constructors/userDeleted.md)  



### Methods that return an object of this type (methods):

[$MadelineProto->account->updateProfile](../methods/account_updateProfile.md)  

[$MadelineProto->users->getUsers](../methods/users_getUsers.md)  

[$MadelineProto->contacts->importCard](../methods/contacts_importCard.md)  

[$MadelineProto->account->updateUsername](../methods/account_updateUsername.md)  

[$MadelineProto->contacts->resolveUsername](../methods/contacts_resolveUsername.md)  

[$MadelineProto->account->changePhone](../methods/account_changePhone.md)  



