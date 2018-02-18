---
title: updateChannelTooLong
description: updateChannelTooLong attributes, type and example
---
## Constructor: updateChannelTooLong  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|pts|[int](../types/int.md) | Optional|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelTooLong = ['_' => 'updateChannelTooLong', 'channel_id' => int, 'pts' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelTooLong", "channel_id": int, "pts": int}
```


Or, if you're into Lua:  


```
updateChannelTooLong={_='updateChannelTooLong', channel_id=int, pts=int}

```


