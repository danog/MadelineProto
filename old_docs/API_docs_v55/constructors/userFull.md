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
|user|[User](../types/User.md) | Yes|
|about|[string](../types/string.md) | Optional|
|link|[contacts\_Link](../types/contacts_Link.md) | Yes|
|profile\_photo|[Photo](../types/Photo.md) | Optional|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|bot\_info|[BotInfo](../types/BotInfo.md) | Optional|



### Type: [UserFull](../types/UserFull.md)


### Example:

```
$userFull = ['_' => 'userFull', 'blocked' => Bool, 'user' => User, 'about' => 'string', 'link' => contacts_Link, 'profile_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'bot_info' => BotInfo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userFull", "blocked": Bool, "user": User, "about": "string", "link": contacts_Link, "profile_photo": Photo, "notify_settings": PeerNotifySettings, "bot_info": BotInfo}
```


Or, if you're into Lua:  


```
userFull={_='userFull', blocked=Bool, user=User, about='string', link=contacts_Link, profile_photo=Photo, notify_settings=PeerNotifySettings, bot_info=BotInfo}

```


