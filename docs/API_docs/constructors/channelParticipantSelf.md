---
title: channelParticipantSelf
description: channelParticipantSelf attributes, type and example
---
## Constructor: channelParticipantSelf  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|inviter\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|



### Type: [ChannelParticipant](../types/ChannelParticipant.md)


### Example:

```
$channelParticipantSelf = ['_' => channelParticipantSelf, 'user_id' => int, 'inviter_id' => int, 'date' => int, ];
```