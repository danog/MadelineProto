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
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|
|username|[string](../types/string.md) | Optional|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|date|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => 'channel', 'creator' => true, 'kicked' => true, 'left' => true, 'editor' => true, 'moderator' => true, 'broadcast' => true, 'verified' => true, 'megagroup' => true, 'id' => int, 'access_hash' => long, 'title' => string, 'username' => string, 'photo' => ChatPhoto, 'date' => int, 'version' => int, ];
```  

