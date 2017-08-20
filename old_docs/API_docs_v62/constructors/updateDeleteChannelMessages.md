---
title: updateDeleteChannelMessages
description: updateDeleteChannelMessages attributes, type and example
---
## Constructor: updateDeleteChannelMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|messages|Array of [int](../types/int.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDeleteChannelMessages = ['_' => 'updateDeleteChannelMessages', 'channel_id' => int, 'messages' => [int], 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateDeleteChannelMessages", "channel_id": int, "messages": [int], "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
updateDeleteChannelMessages={_='updateDeleteChannelMessages', channel_id=int, messages={int}, pts=int, pts_count=int}

```


