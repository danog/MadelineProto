---
title: channelParticipantBanned
description: channelParticipantBanned attributes, type and example
---
## Constructor: channelParticipantBanned  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|left|[Bool](../types/Bool.md) | Optional|
|user\_id|[int](../types/int.md) | Yes|
|kicked\_by|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|banned\_rights|[ChannelBannedRights](../types/ChannelBannedRights.md) | Yes|



### Type: [ChannelParticipant](../types/ChannelParticipant.md)


### Example:

```
$channelParticipantBanned = ['_' => 'channelParticipantBanned', 'left' => Bool, 'user_id' => int, 'kicked_by' => int, 'date' => int, 'banned_rights' => ChannelBannedRights];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelParticipantBanned", "left": Bool, "user_id": int, "kicked_by": int, "date": int, "banned_rights": ChannelBannedRights}
```


Or, if you're into Lua:  


```
channelParticipantBanned={_='channelParticipantBanned', left=Bool, user_id=int, kicked_by=int, date=int, banned_rights=ChannelBannedRights}

```


