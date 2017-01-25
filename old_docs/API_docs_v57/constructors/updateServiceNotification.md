---
title: updateServiceNotification
description: updateServiceNotification attributes, type and example
---
## Constructor: updateServiceNotification  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|type|[string](../types/string.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|
|popup|[Bool](../types/Bool.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateServiceNotification = ['_' => 'updateServiceNotification', 'type' => string, 'message' => string, 'media' => MessageMedia, 'popup' => Bool, ];
```  

