---
title: channelAdminLogEventActionParticipantToggleAdmin
description: channelAdminLogEventActionParticipantToggleAdmin attributes, type and example
---
## Constructor: channelAdminLogEventActionParticipantToggleAdmin  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_participant|[ChannelParticipant](../types/ChannelParticipant.md) | Yes|
|new\_participant|[ChannelParticipant](../types/ChannelParticipant.md) | Yes|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionParticipantToggleAdmin = ['_' => 'channelAdminLogEventActionParticipantToggleAdmin', 'prev_participant' => ChannelParticipant, 'new_participant' => ChannelParticipant];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionParticipantToggleAdmin", "prev_participant": ChannelParticipant, "new_participant": ChannelParticipant}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionParticipantToggleAdmin={_='channelAdminLogEventActionParticipantToggleAdmin', prev_participant=ChannelParticipant, new_participant=ChannelParticipant}

```


