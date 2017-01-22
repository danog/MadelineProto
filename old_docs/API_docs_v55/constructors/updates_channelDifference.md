---
title: updates.channelDifference
description: updates_channelDifference attributes, type and example
---
## Constructor: updates.channelDifference  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|final|[Bool](../types/Bool.md) | Optional|
|pts|[int](../types/int.md) | Required|
|timeout|[int](../types/int.md) | Optional|
|new\_messages|Array of [Message](../types/Message.md) | Required|
|other\_updates|Array of [Update](../types/Update.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [updates\_ChannelDifference](../types/updates_ChannelDifference.md)


### Example:

```
$updates_channelDifference = ['_' => 'updates.channelDifference', 'final' => true, 'pts' => int, 'timeout' => int, 'new_messages' => [Vector t], 'other_updates' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```  

