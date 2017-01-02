---
title: updateShortMessage
description: updateShortMessage attributes, type and example
---
## Constructor: updateShortMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|fwd\_from\_id|[int](../types/int.md) | Optional|
|fwd\_date|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortMessage = ['_' => 'updateShortMessage', 'id' => int, 'user_id' => int, 'message' => string, 'pts' => int, 'pts_count' => int, 'date' => int, 'fwd_from_id' => int, 'fwd_date' => int, 'reply_to_msg_id' => int, ];
```  

