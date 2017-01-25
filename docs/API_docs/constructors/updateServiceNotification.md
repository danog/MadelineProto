---
title: updateServiceNotification
description: updateServiceNotification attributes, type and example
---
## Constructor: updateServiceNotification  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|popup|[Bool](../types/Bool.md) | Optional|
|inbox\_date|[int](../types/int.md) | Optional|
|type|[string](../types/string.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateServiceNotification = ['_' => 'updateServiceNotification', 'popup' => true, 'inbox_date' => int, 'type' => string, 'message' => string, 'media' => MessageMedia, 'entities' => [Vector t], ];
```  

