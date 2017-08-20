---
title: userFull
description: userFull attributes, type and example
---
## Constructor: userFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|blocked|[Bool](../types/Bool.md) | Optional|
|phone\_calls\_available|[Bool](../types/Bool.md) | Optional|
|phone\_calls\_private|[Bool](../types/Bool.md) | Optional|
|user|[User](../types/User.md) | Yes|
|about|[string](../types/string.md) | Optional|
|link|[contacts\_Link](../types/contacts_Link.md) | Yes|
|profile\_photo|[Photo](../types/Photo.md) | Optional|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|bot\_info|[BotInfo](../types/BotInfo.md) | Optional|
|common\_chats\_count|[int](../types/int.md) | Yes|



### Type: [UserFull](../types/UserFull.md)


### Example:

```
$userFull = ['_' => 'userFull', 'blocked' => Bool, 'phone_calls_available' => Bool, 'phone_calls_private' => Bool, 'user' => User, 'about' => 'string', 'link' => contacts_Link, 'profile_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'bot_info' => BotInfo, 'common_chats_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userFull", "blocked": Bool, "phone_calls_available": Bool, "phone_calls_private": Bool, "user": User, "about": "string", "link": contacts_Link, "profile_photo": Photo, "notify_settings": PeerNotifySettings, "bot_info": BotInfo, "common_chats_count": int}
```


Or, if you're into Lua:  


```
userFull={_='userFull', blocked=Bool, phone_calls_available=Bool, phone_calls_private=Bool, user=User, about='string', link=contacts_Link, profile_photo=Photo, notify_settings=PeerNotifySettings, bot_info=BotInfo, common_chats_count=int}

```


