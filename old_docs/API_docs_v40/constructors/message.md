---
title: message
description: message attributes, type and example
---
## Constructor: message  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Yes|
|fwd\_from\_id|[int](../types/int.md) | Optional|
|fwd\_date|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|date|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [Message](../types/Message.md)


### Example:

```
$message = ['_' => 'message', 'id' => int, 'from_id' => int, 'to_id' => Peer, 'fwd_from_id' => int, 'fwd_date' => int, 'reply_to_msg_id' => int, 'date' => int, 'message' => 'string', 'media' => MessageMedia, 'reply_markup' => ReplyMarkup, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "message", "id": int, "from_id": int, "to_id": Peer, "fwd_from_id": int, "fwd_date": int, "reply_to_msg_id": int, "date": int, "message": "string", "media": MessageMedia, "reply_markup": ReplyMarkup, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
message={_='message', id=int, from_id=int, to_id=Peer, fwd_from_id=int, fwd_date=int, reply_to_msg_id=int, date=int, message='string', media=MessageMedia, reply_markup=ReplyMarkup, entities={MessageEntity}}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


