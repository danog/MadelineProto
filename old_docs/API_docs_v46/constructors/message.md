---
title: message
description: message attributes, type and example
---
## Constructor: message  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|unread|[Bool](../types/Bool.md) | Optional|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Required|
|fwd\_from\_id|[Peer](../types/Peer.md) | Optional|
|fwd\_date|[int](../types/int.md) | Optional|
|via\_bot\_id|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|date|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|views|[int](../types/int.md) | Optional|



### Type: [Message](../types/Message.md)


### Example:

```
$message = ['_' => 'message', 'unread' => true, 'out' => true, 'mentioned' => true, 'media_unread' => true, 'id' => int, 'from_id' => int, 'to_id' => Peer, 'fwd_from_id' => Peer, 'fwd_date' => int, 'via_bot_id' => int, 'reply_to_msg_id' => int, 'date' => int, 'message' => string, 'media' => MessageMedia, 'reply_markup' => ReplyMarkup, 'entities' => [Vector t], 'views' => int, ];
```  

