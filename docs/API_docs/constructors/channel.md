---
title: channel
description: channel attributes, type and example
---
## Constructor: channel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|creator|[Bool](../types/Bool.md) | Optional|
|kicked|[Bool](../types/Bool.md) | Optional|
|left|[Bool](../types/Bool.md) | Optional|
|editor|[Bool](../types/Bool.md) | Optional|
|moderator|[Bool](../types/Bool.md) | Optional|
|broadcast|[Bool](../types/Bool.md) | Optional|
|verified|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|restricted|[Bool](../types/Bool.md) | Optional|
|democracy|[Bool](../types/Bool.md) | Optional|
|signatures|[Bool](../types/Bool.md) | Optional|
|min|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Optional|
|title|[string](../types/string.md) | Required|
|username|[string](../types/string.md) | Optional|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|date|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|
|restriction\_reason|[string](../types/string.md) | Optional|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => channel', 'creator' => true, 'kicked' => true, 'left' => true, 'editor' => true, 'moderator' => true, 'broadcast' => true, 'verified' => true, 'megagroup' => true, 'restricted' => true, 'democracy' => true, 'signatures' => true, 'min' => true, 'id' => int, 'access_hash' => long, 'title' => string, 'username' => string, 'photo' => ChatPhoto, 'date' => int, 'version' => int, 'restriction_reason' => string, ];
```