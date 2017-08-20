---
title: updateShortSentMessage
description: updateShortSentMessage attributes, type and example
---
## Constructor: updateShortSentMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|unread|[Bool](../types/Bool.md) | Optional|
|out|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortSentMessage = ['_' => 'updateShortSentMessage', 'unread' => Bool, 'out' => Bool, 'id' => int, 'pts' => int, 'pts_count' => int, 'date' => int, 'media' => MessageMedia, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateShortSentMessage", "unread": Bool, "out": Bool, "id": int, "pts": int, "pts_count": int, "date": int, "media": MessageMedia, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
updateShortSentMessage={_='updateShortSentMessage', unread=Bool, out=Bool, id=int, pts=int, pts_count=int, date=int, media=MessageMedia, entities={MessageEntity}}

```


