---
title: userTypeBot
description: Bot (see https: core.telegram.org/bots)
---
## Constructor: userTypeBot  
[Back to constructors index](index.md)



Bot (see https: core.telegram.org/bots)

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|can\_join\_group\_chats|[Bool](../types/Bool.md) | Yes|If true, bot can be invited to group and supergroup chats|
|can\_read\_all\_group\_chat\_messages|[Bool](../types/Bool.md) | Yes|If true, bot can read all group or supergroup chat messages, not only addressed to him. In private chats bot always can read all messages|
|is\_inline|[Bool](../types/Bool.md) | Yes|True, if bot supports inline queries|
|inline\_query\_placeholder|[string](../types/string.md) | Yes|Placeholder for inline query|
|need\_location|[Bool](../types/Bool.md) | Yes|If true, user location should be sent with every inline query to this bot|



### Type: [UserType](../types/UserType.md)


### Example:

```
$userTypeBot = ['_' => 'userTypeBot', 'can_join_group_chats' => Bool, 'can_read_all_group_chat_messages' => Bool, 'is_inline' => Bool, 'inline_query_placeholder' => 'string', 'need_location' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userTypeBot", "can_join_group_chats": Bool, "can_read_all_group_chat_messages": Bool, "is_inline": Bool, "inline_query_placeholder": "string", "need_location": Bool}
```


Or, if you're into Lua:  


```
userTypeBot={_='userTypeBot', can_join_group_chats=Bool, can_read_all_group_chat_messages=Bool, is_inline=Bool, inline_query_placeholder='string', need_location=Bool}

```


