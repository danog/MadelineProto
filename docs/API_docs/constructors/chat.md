---
title: chat
description: chat attributes, type and example
---
## Constructor: chat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|creator|[Bool](../types/Bool.md) | Optional|
|kicked|[Bool](../types/Bool.md) | Optional|
|left|[Bool](../types/Bool.md) | Optional|
|admins\_enabled|[Bool](../types/Bool.md) | Optional|
|admin|[Bool](../types/Bool.md) | Optional|
|deactivated|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|title|[string](../types/string.md) | Required|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|participants\_count|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|
|migrated\_to|[InputChannel](../types/InputChannel.md) | Optional|



### Type: [Chat](../types/Chat.md)


### Example:

```
$chat = ['_' => chat, 'creator' => true, 'kicked' => true, 'left' => true, 'admins_enabled' => true, 'admin' => true, 'deactivated' => true, 'id' => int, 'title' => string, 'photo' => ChatPhoto, 'participants_count' => int, 'date' => int, 'version' => int, 'migrated_to' => InputChannel, ];
```