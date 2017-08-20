---
title: updates.channelDifferenceTooLong
description: updates_channelDifferenceTooLong attributes, type and example
---
## Constructor: updates.channelDifferenceTooLong  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|final|[Bool](../types/Bool.md) | Optional|
|pts|[int](../types/int.md) | Yes|
|timeout|[int](../types/int.md) | Optional|
|top\_message|[int](../types/int.md) | Yes|
|read\_inbox\_max\_id|[int](../types/int.md) | Yes|
|read\_outbox\_max\_id|[int](../types/int.md) | Yes|
|unread\_count|[int](../types/int.md) | Yes|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [updates\_ChannelDifference](../types/updates_ChannelDifference.md)


### Example:

```
$updates_channelDifferenceTooLong = ['_' => 'updates.channelDifferenceTooLong', 'final' => Bool, 'pts' => int, 'timeout' => int, 'top_message' => int, 'read_inbox_max_id' => int, 'read_outbox_max_id' => int, 'unread_count' => int, 'messages' => [Message], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates.channelDifferenceTooLong", "final": Bool, "pts": int, "timeout": int, "top_message": int, "read_inbox_max_id": int, "read_outbox_max_id": int, "unread_count": int, "messages": [Message], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
updates_channelDifferenceTooLong={_='updates.channelDifferenceTooLong', final=Bool, pts=int, timeout=int, top_message=int, read_inbox_max_id=int, read_outbox_max_id=int, unread_count=int, messages={Message}, chats={Chat}, users={User}}

```


