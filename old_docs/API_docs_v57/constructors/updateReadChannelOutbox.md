---
title: updateReadChannelOutbox
description: updateReadChannelOutbox attributes, type and example
---
## Constructor: updateReadChannelOutbox  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadChannelOutbox = ['_' => 'updateReadChannelOutbox', 'channel_id' => int, 'max_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateReadChannelOutbox", "channel_id": int, "max_id": int}
```


Or, if you're into Lua:  


```
updateReadChannelOutbox={_='updateReadChannelOutbox', channel_id=int, max_id=int}

```


