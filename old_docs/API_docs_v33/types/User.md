---
title: User
description: constructors and methods of type User
---
## Type: User  
[Back to types index](index.md)



The following syntaxes can also be used:

```
$help_appChangelog = '@username'; // Username

$help_appChangelog = 44700; // bot API id (users)
$help_appChangelog = -492772765; // bot API id (chats)
$help_appChangelog = -10038575794; // bot API id (channels)

$help_appChangelog = 'user#44700'; // tg-cli style id (users)
$help_appChangelog = 'chat#492772765'; // tg-cli style id (chats)
$help_appChangelog = 'channel#38575794'; // tg-cli style id (channels)
```


### Possible values (constructors):

[userEmpty](../constructors/userEmpty.md)  

[user](../constructors/user.md)  



### Methods that return an object of this type (methods):

[$MadelineProto->account->updateProfile](../methods/account_updateProfile.md)  

[$MadelineProto->users->getUsers](../methods/users_getUsers.md)  

[$MadelineProto->contacts->importCard](../methods/contacts_importCard.md)  

[$MadelineProto->account->updateUsername](../methods/account_updateUsername.md)  

[$MadelineProto->contacts->resolveUsername](../methods/contacts_resolveUsername.md)  

[$MadelineProto->account->changePhone](../methods/account_changePhone.md)  



