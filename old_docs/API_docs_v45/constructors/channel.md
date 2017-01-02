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
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|
|username|[string](../types/string.md) | Optional|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|date|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|
|restriction\_reason|[string](../types/string.md) | Optional|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => 'channel', 'creator' => true, 'kicked' => true, 'left' => true, 'editor' => true, 'moderator' => true, 'broadcast' => true, 'verified' => true, 'megagroup' => true, 'restricted' => true, 'id' => int, 'access_hash' => long, 'title' => string, 'username' => string, 'photo' => ChatPhoto, 'date' => int, 'version' => int, 'restriction_reason' => string, ];
```  

The following syntaxes can also be used:

```
$channel = '@username'; // Username

$channel = 44700; // bot API id (users)
$channel = -492772765; // bot API id (chats)
$channel = -10038575794; // bot API id (channels)

$channel = 'user#44700'; // tg-cli style id (users)
$channel = 'chat#492772765'; // tg-cli style id (chats)
$channel = 'channel#38575794'; // tg-cli style id (channels)
```