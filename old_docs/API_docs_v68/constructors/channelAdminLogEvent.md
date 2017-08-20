---
title: channelAdminLogEvent
description: channelAdminLogEvent attributes, type and example
---
## Constructor: channelAdminLogEvent  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|action|[ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md) | Yes|



### Type: [ChannelAdminLogEvent](../types/ChannelAdminLogEvent.md)


### Example:

```
$channelAdminLogEvent = ['_' => 'channelAdminLogEvent', 'id' => long, 'date' => int, 'user_id' => int, 'action' => ChannelAdminLogEventAction];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEvent", "id": long, "date": int, "user_id": int, "action": ChannelAdminLogEventAction}
```


Or, if you're into Lua:  


```
channelAdminLogEvent={_='channelAdminLogEvent', id=long, date=int, user_id=int, action=ChannelAdminLogEventAction}

```


