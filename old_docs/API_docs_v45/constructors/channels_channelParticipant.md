---
title: channels.channelParticipant
description: channels_channelParticipant attributes, type and example
---
## Constructor: channels.channelParticipant  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|participant|[ChannelParticipant](../types/ChannelParticipant.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)


### Example:

```
$channels_channelParticipant = ['_' => 'channels.channelParticipant', 'participant' => ChannelParticipant, 'users' => [Vector t], ];
```  

