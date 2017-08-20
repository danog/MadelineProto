---
title: channelAdminLogEventActionParticipantToggleBan
description: channelAdminLogEventActionParticipantToggleBan attributes, type and example
---
## Constructor: channelAdminLogEventActionParticipantToggleBan  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_participant|[ChannelParticipant](../types/ChannelParticipant.md) | Yes|
|new\_participant|[ChannelParticipant](../types/ChannelParticipant.md) | Yes|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionParticipantToggleBan = ['_' => 'channelAdminLogEventActionParticipantToggleBan', 'prev_participant' => ChannelParticipant, 'new_participant' => ChannelParticipant];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionParticipantToggleBan", "prev_participant": ChannelParticipant, "new_participant": ChannelParticipant}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionParticipantToggleBan={_='channelAdminLogEventActionParticipantToggleBan', prev_participant=ChannelParticipant, new_participant=ChannelParticipant}

```


