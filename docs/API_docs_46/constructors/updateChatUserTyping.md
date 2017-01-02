---
title: updateChatUserTyping
description: updateChatUserTyping attributes, type and example
---
## Constructor: updateChatUserTyping  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|action|[SendMessageAction](../types/SendMessageAction.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatUserTyping = ['_' => 'updateChatUserTyping', 'chat_id' => int, 'user_id' => int, 'action' => SendMessageAction, ];
```