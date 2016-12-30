---
title: channelParticipantModerator
description: channelParticipantModerator attributes, type and example
---
## Constructor: channelParticipantModerator  
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
$channelParticipantModerator = ['_' => 'channelParticipantModerator', 'user_id' => int, 'inviter_id' => int, 'date' => int, ];
```