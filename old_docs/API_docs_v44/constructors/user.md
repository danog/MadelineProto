---
title: user
description: user attributes, type and example
---
## Constructor: user  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|self|[Bool](../types/Bool.md) | Optional|
|contact|[Bool](../types/Bool.md) | Optional|
|mutual\_contact|[Bool](../types/Bool.md) | Optional|
|deleted|[Bool](../types/Bool.md) | Optional|
|bot|[Bool](../types/Bool.md) | Optional|
|bot\_chat\_history|[Bool](../types/Bool.md) | Optional|
|bot\_nochats|[Bool](../types/Bool.md) | Optional|
|verified|[Bool](../types/Bool.md) | Optional|
|restricted|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Optional|
|first\_name|[string](../types/string.md) | Optional|
|last\_name|[string](../types/string.md) | Optional|
|username|[string](../types/string.md) | Optional|
|phone|[string](../types/string.md) | Optional|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Optional|
|status|[UserStatus](../types/UserStatus.md) | Optional|
|bot\_info\_version|[int](../types/int.md) | Optional|
|restiction\_reason|[string](../types/string.md) | Optional|



### Type: [User](../types/User.md)


### Example:

```
$user = ['_' => 'user', 'self' => Bool, 'contact' => Bool, 'mutual_contact' => Bool, 'deleted' => Bool, 'bot' => Bool, 'bot_chat_history' => Bool, 'bot_nochats' => Bool, 'verified' => Bool, 'restricted' => Bool, 'id' => int, 'access_hash' => long, 'first_name' => 'string', 'last_name' => 'string', 'username' => 'string', 'phone' => 'string', 'photo' => UserProfilePhoto, 'status' => UserStatus, 'bot_info_version' => int, 'restiction_reason' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "user", "self": Bool, "contact": Bool, "mutual_contact": Bool, "deleted": Bool, "bot": Bool, "bot_chat_history": Bool, "bot_nochats": Bool, "verified": Bool, "restricted": Bool, "id": int, "access_hash": long, "first_name": "string", "last_name": "string", "username": "string", "phone": "string", "photo": UserProfilePhoto, "status": UserStatus, "bot_info_version": int, "restiction_reason": "string"}
```


Or, if you're into Lua:  


```
user={_='user', self=Bool, contact=Bool, mutual_contact=Bool, deleted=Bool, bot=Bool, bot_chat_history=Bool, bot_nochats=Bool, verified=Bool, restricted=Bool, id=int, access_hash=long, first_name='string', last_name='string', username='string', phone='string', photo=UserProfilePhoto, status=UserStatus, bot_info_version=int, restiction_reason='string'}

```


