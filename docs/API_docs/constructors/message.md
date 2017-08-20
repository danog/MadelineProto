---
title: message
description: message attributes, type and example
---
## Constructor: message  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|post|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Yes|
|fwd\_from|[MessageFwdHeader](../types/MessageFwdHeader.md) | Optional|
|via\_bot\_id|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|date|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|views|[int](../types/int.md) | Optional|
|edit\_date|[int](../types/int.md) | Optional|
|post\_author|[string](../types/string.md) | Optional|



### Type: [Message](../types/Message.md)


### Example:

```
$message = ['_' => 'message', 'out' => Bool, 'mentioned' => Bool, 'media_unread' => Bool, 'silent' => Bool, 'post' => Bool, 'id' => int, 'from_id' => int, 'to_id' => Peer, 'fwd_from' => MessageFwdHeader, 'via_bot_id' => int, 'reply_to_msg_id' => int, 'date' => int, 'message' => 'string', 'media' => MessageMedia, 'reply_markup' => ReplyMarkup, 'entities' => [MessageEntity], 'views' => int, 'edit_date' => int, 'post_author' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "message", "out": Bool, "mentioned": Bool, "media_unread": Bool, "silent": Bool, "post": Bool, "id": int, "from_id": int, "to_id": Peer, "fwd_from": MessageFwdHeader, "via_bot_id": int, "reply_to_msg_id": int, "date": int, "message": "string", "media": MessageMedia, "reply_markup": ReplyMarkup, "entities": [MessageEntity], "views": int, "edit_date": int, "post_author": "string"}
```


Or, if you're into Lua:  


```
message={_='message', out=Bool, mentioned=Bool, media_unread=Bool, silent=Bool, post=Bool, id=int, from_id=int, to_id=Peer, fwd_from=MessageFwdHeader, via_bot_id=int, reply_to_msg_id=int, date=int, message='string', media=MessageMedia, reply_markup=ReplyMarkup, entities={MessageEntity}, views=int, edit_date=int, post_author='string'}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


