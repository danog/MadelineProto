---
title: updateChatAdmins
description: updateChatAdmins attributes, type and example
---
## Constructor: updateChatAdmins  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|enabled|[Bool](../types/Bool.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatAdmins = ['_' => updateChatAdmins, 'chat_id' => int, 'enabled' => Bool, 'version' => int, ];
```