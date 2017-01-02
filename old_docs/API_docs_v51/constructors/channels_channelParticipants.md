---
title: channels_channelParticipants
description: channels_channelParticipants attributes, type and example
---
## Constructor: channels\_channelParticipants  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|count|[int](../types/int.md) | Required|
|participants|Array of [ChannelParticipant](../types/ChannelParticipant.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)


### Example:

```
$channels_channelParticipants = ['_' => 'channels_channelParticipants', 'count' => int, 'participants' => [Vector t], 'users' => [Vector t], ];
```  

