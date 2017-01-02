---
title: geoChat
description: geoChat attributes, type and example
---
## Constructor: geoChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|
|address|[string](../types/string.md) | Required|
|venue|[string](../types/string.md) | Required|
|geo|[GeoPoint](../types/GeoPoint.md) | Required|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|participants\_count|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|checked\_in|[Bool](../types/Bool.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$geoChat = ['_' => 'geoChat', 'id' => int, 'access_hash' => long, 'title' => string, 'address' => string, 'venue' => string, 'geo' => GeoPoint, 'photo' => ChatPhoto, 'participants_count' => int, 'date' => int, 'checked_in' => Bool, 'version' => int, ];
```  

The following syntaxes can also be used:

```
$geoChat = '@username'; // Username

$geoChat = 44700; // bot API id (users)
$geoChat = -492772765; // bot API id (chats)
$geoChat = -10038575794; // bot API id (channels)

$geoChat = 'user#44700'; // tg-cli style id (users)
$geoChat = 'chat#492772765'; // tg-cli style id (chats)
$geoChat = 'channel#38575794'; // tg-cli style id (channels)
```