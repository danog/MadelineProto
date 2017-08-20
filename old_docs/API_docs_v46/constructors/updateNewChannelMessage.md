---
title: updateNewChannelMessage
description: updateNewChannelMessage attributes, type and example
---
## Constructor: updateNewChannelMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[Message](../types/Message.md) | Yes|
|channel\_pts|[int](../types/int.md) | Yes|
|channel\_pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewChannelMessage = ['_' => 'updateNewChannelMessage', 'message' => Message, 'channel_pts' => int, 'channel_pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewChannelMessage", "message": Message, "channel_pts": int, "channel_pts_count": int}
```


Or, if you're into Lua:  


```
updateNewChannelMessage={_='updateNewChannelMessage', message=Message, channel_pts=int, channel_pts_count=int}

```


