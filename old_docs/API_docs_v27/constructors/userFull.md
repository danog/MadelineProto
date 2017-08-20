---
title: userFull
description: userFull attributes, type and example
---
## Constructor: userFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user|[User](../types/User.md) | Yes|
|link|[contacts\_Link](../types/contacts_Link.md) | Yes|
|profile\_photo|[Photo](../types/Photo.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|blocked|[Bool](../types/Bool.md) | Yes|
|real\_first\_name|[string](../types/string.md) | Yes|
|real\_last\_name|[string](../types/string.md) | Yes|



### Type: [UserFull](../types/UserFull.md)


### Example:

```
$userFull = ['_' => 'userFull', 'user' => User, 'link' => contacts_Link, 'profile_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'blocked' => Bool, 'real_first_name' => 'string', 'real_last_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userFull", "user": User, "link": contacts_Link, "profile_photo": Photo, "notify_settings": PeerNotifySettings, "blocked": Bool, "real_first_name": "string", "real_last_name": "string"}
```


Or, if you're into Lua:  


```
userFull={_='userFull', user=User, link=contacts_Link, profile_photo=Photo, notify_settings=PeerNotifySettings, blocked=Bool, real_first_name='string', real_last_name='string'}

```


