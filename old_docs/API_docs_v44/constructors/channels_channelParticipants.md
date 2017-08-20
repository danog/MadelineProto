---
title: channels.channelParticipants
description: channels_channelParticipants attributes, type and example
---
## Constructor: channels.channelParticipants  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|count|[int](../types/int.md) | Yes|
|participants|Array of [ChannelParticipant](../types/ChannelParticipant.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)


### Example:

```
$channels_channelParticipants = ['_' => 'channels.channelParticipants', 'count' => int, 'participants' => [ChannelParticipant], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channels.channelParticipants", "count": int, "participants": [ChannelParticipant], "users": [User]}
```


Or, if you're into Lua:  


```
channels_channelParticipants={_='channels.channelParticipants', count=int, participants={ChannelParticipant}, users={User}}

```


