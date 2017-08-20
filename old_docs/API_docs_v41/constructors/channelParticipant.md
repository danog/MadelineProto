---
title: channelParticipant
description: channelParticipant attributes, type and example
---
## Constructor: channelParticipant  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [ChannelParticipant](../types/ChannelParticipant.md)


### Example:

```
$channelParticipant = ['_' => 'channelParticipant', 'user_id' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelParticipant", "user_id": int, "date": int}
```


Or, if you're into Lua:  


```
channelParticipant={_='channelParticipant', user_id=int, date=int}

```


