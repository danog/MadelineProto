---
title: updateChannelGroup
description: updateChannelGroup attributes, type and example
---
## Constructor: updateChannelGroup  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|group|[MessageGroup](../types/MessageGroup.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelGroup = ['_' => 'updateChannelGroup', 'channel_id' => int, 'group' => MessageGroup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelGroup", "channel_id": int, "group": MessageGroup}
```


Or, if you're into Lua:  


```
updateChannelGroup={_='updateChannelGroup', channel_id=int, group=MessageGroup}

```


