---
title: updateEditChannelMessage
description: updateEditChannelMessage attributes, type and example
---
## Constructor: updateEditChannelMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[Message](../types/Message.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateEditChannelMessage = ['_' => 'updateEditChannelMessage', 'message' => Message, 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateEditChannelMessage", "message": Message, "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
updateEditChannelMessage={_='updateEditChannelMessage', message=Message, pts=int, pts_count=int}

```


