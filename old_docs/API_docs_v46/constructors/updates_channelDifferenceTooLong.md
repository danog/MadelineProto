---
title: updates.channelDifferenceTooLong
description: updates_channelDifferenceTooLong attributes, type and example
---
## Constructor: updates.channelDifferenceTooLong  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|final|[Bool](../types/Bool.md) | Optional|
|channel\_pts|[int](../types/int.md) | Required|
|timeout|[int](../types/int.md) | Optional|
|top\_message|[int](../types/int.md) | Required|
|top\_important\_message|[int](../types/int.md) | Required|
|read\_inbox\_max\_id|[int](../types/int.md) | Required|
|unread\_count|[int](../types/int.md) | Required|
|unread\_important\_count|[int](../types/int.md) | Required|
|messages|Array of [MTMessage](../types/MTMessage.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [updates\_ChannelDifference](../types/updates_ChannelDifference.md)


### Example:

```
$updates_channelDifferenceTooLong = ['_' => 'updates.channelDifferenceTooLong', 'final' => true, 'channel_pts' => int, 'timeout' => int, 'top_message' => int, 'top_important_message' => int, 'read_inbox_max_id' => int, 'unread_count' => int, 'unread_important_count' => int, 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```  

