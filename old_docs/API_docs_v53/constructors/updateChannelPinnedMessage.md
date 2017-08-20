---
title: updateChannelPinnedMessage
description: updateChannelPinnedMessage attributes, type and example
---
## Constructor: updateChannelPinnedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelPinnedMessage = ['_' => 'updateChannelPinnedMessage', 'channel_id' => int, 'id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelPinnedMessage", "channel_id": int, "id": int}
```


Or, if you're into Lua:  


```
updateChannelPinnedMessage={_='updateChannelPinnedMessage', channel_id=int, id=int}

```


