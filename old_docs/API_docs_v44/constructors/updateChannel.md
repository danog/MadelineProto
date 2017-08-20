---
title: updateChannel
description: updateChannel attributes, type and example
---
## Constructor: updateChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannel = ['_' => 'updateChannel', 'channel_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannel", "channel_id": int}
```


Or, if you're into Lua:  


```
updateChannel={_='updateChannel', channel_id=int}

```


