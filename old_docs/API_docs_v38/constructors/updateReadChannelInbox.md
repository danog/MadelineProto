---
title: updateReadChannelInbox
description: updateReadChannelInbox attributes, type and example
---
## Constructor: updateReadChannelInbox  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadChannelInbox = ['_' => 'updateReadChannelInbox', 'channel_id' => int, 'max_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateReadChannelInbox", "channel_id": int, "max_id": int}
```


Or, if you're into Lua:  


```
updateReadChannelInbox={_='updateReadChannelInbox', channel_id=int, max_id=int}

```


