---
title: updateChannelAvailableMessages
description: updateChannelAvailableMessages attributes, type and example
---
## Constructor: updateChannelAvailableMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|available\_min\_id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelAvailableMessages = ['_' => 'updateChannelAvailableMessages', 'channel_id' => int, 'available_min_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelAvailableMessages", "channel_id": int, "available_min_id": int}
```


Or, if you're into Lua:  


```
updateChannelAvailableMessages={_='updateChannelAvailableMessages', channel_id=int, available_min_id=int}

```


