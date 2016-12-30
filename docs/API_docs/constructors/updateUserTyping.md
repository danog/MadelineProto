---
title: updateUserTyping
description: updateUserTyping attributes, type and example
---
## Constructor: updateUserTyping  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|action|[SendMessageAction](../types/SendMessageAction.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserTyping = ['_' => updateUserTyping, 'user_id' => int, 'action' => SendMessageAction, ];
```