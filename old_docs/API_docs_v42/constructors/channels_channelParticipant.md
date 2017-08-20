---
title: channels.channelParticipant
description: channels_channelParticipant attributes, type and example
---
## Constructor: channels.channelParticipant  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|participant|[ChannelParticipant](../types/ChannelParticipant.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)


### Example:

```
$channels_channelParticipant = ['_' => 'channels.channelParticipant', 'participant' => ChannelParticipant, 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channels.channelParticipant", "participant": ChannelParticipant, "users": [User]}
```


Or, if you're into Lua:  


```
channels_channelParticipant={_='channels.channelParticipant', participant=ChannelParticipant, users={User}}

```


