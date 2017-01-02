---
title: draftMessage
description: draftMessage attributes, type and example
---
## Constructor: draftMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|message|[string](../types/string.md) | Required|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|date|[int](../types/int.md) | Required|



### Type: [DraftMessage](../types/DraftMessage.md)


### Example:

```
$draftMessage = ['_' => 'draftMessage', 'no_webpage' => true, 'reply_to_msg_id' => int, 'message' => string, 'entities' => [Vector t], 'date' => int, ];
```