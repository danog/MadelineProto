---
title: userFull
description: Gives full information about a user (except full list of profile photos)
---
## Constructor: userFull  
[Back to constructors index](index.md)



Gives full information about a user (except full list of profile photos)

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user|[user](../types/user.md) | Yes|General info about the user|
|is\_blocked|[Bool](../types/Bool.md) | Yes|Is user blacklisted by current user|
|about|[string](../types/string.md) | Yes|Short user description|
|common\_chat\_count|[int](../types/int.md) | Yes|Number of common chats between the user and current user, 0 for the current user|
|bot\_info|[botInfo](../types/botInfo.md) | Yes|Information about bot if user is a bot, nullable|



### Type: [UserFull](../types/UserFull.md)


### Example:

```
$userFull = ['_' => 'userFull', 'user' => user, 'is_blocked' => Bool, 'about' => 'string', 'common_chat_count' => int, 'bot_info' => botInfo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userFull", "user": user, "is_blocked": Bool, "about": "string", "common_chat_count": int, "bot_info": botInfo}
```


Or, if you're into Lua:  


```
userFull={_='userFull', user=user, is_blocked=Bool, about='string', common_chat_count=int, bot_info=botInfo}

```


