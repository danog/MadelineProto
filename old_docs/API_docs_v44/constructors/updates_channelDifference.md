---
title: updates.channelDifference
description: updates_channelDifference attributes, type and example
---
## Constructor: updates.channelDifference  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|final|[Bool](../types/Bool.md) | Optional|
|pts|[int](../types/int.md) | Yes|
|timeout|[int](../types/int.md) | Optional|
|new\_messages|Array of [Message](../types/Message.md) | Yes|
|other\_updates|Array of [Update](../types/Update.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [updates\_ChannelDifference](../types/updates_ChannelDifference.md)


### Example:

```
$updates_channelDifference = ['_' => 'updates.channelDifference', 'final' => Bool, 'pts' => int, 'timeout' => int, 'new_messages' => [Message], 'other_updates' => [Update], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates.channelDifference", "final": Bool, "pts": int, "timeout": int, "new_messages": [Message], "other_updates": [Update], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
updates_channelDifference={_='updates.channelDifference', final=Bool, pts=int, timeout=int, new_messages={Message}, other_updates={Update}, chats={Chat}, users={User}}

```


