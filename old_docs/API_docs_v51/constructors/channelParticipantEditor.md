---
title: channelParticipantEditor
description: channelParticipantEditor attributes, type and example
---
## Constructor: channelParticipantEditor  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|inviter\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [ChannelParticipant](../types/ChannelParticipant.md)


### Example:

```
$channelParticipantEditor = ['_' => 'channelParticipantEditor', 'user_id' => int, 'inviter_id' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelParticipantEditor", "user_id": int, "inviter_id": int, "date": int}
```


Or, if you're into Lua:  


```
channelParticipantEditor={_='channelParticipantEditor', user_id=int, inviter_id=int, date=int}

```


