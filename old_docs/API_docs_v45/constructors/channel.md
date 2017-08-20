---
title: channel
description: channel attributes, type and example
---
## Constructor: channel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|creator|[Bool](../types/Bool.md) | Optional|
|kicked|[Bool](../types/Bool.md) | Optional|
|left|[Bool](../types/Bool.md) | Optional|
|editor|[Bool](../types/Bool.md) | Optional|
|moderator|[Bool](../types/Bool.md) | Optional|
|broadcast|[Bool](../types/Bool.md) | Optional|
|verified|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|restricted|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|title|[string](../types/string.md) | Yes|
|username|[string](../types/string.md) | Optional|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Yes|
|date|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|
|restriction\_reason|[string](../types/string.md) | Optional|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => 'channel', 'creator' => Bool, 'kicked' => Bool, 'left' => Bool, 'editor' => Bool, 'moderator' => Bool, 'broadcast' => Bool, 'verified' => Bool, 'megagroup' => Bool, 'restricted' => Bool, 'id' => int, 'access_hash' => long, 'title' => 'string', 'username' => 'string', 'photo' => ChatPhoto, 'date' => int, 'version' => int, 'restriction_reason' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channel", "creator": Bool, "kicked": Bool, "left": Bool, "editor": Bool, "moderator": Bool, "broadcast": Bool, "verified": Bool, "megagroup": Bool, "restricted": Bool, "id": int, "access_hash": long, "title": "string", "username": "string", "photo": ChatPhoto, "date": int, "version": int, "restriction_reason": "string"}
```


Or, if you're into Lua:  


```
channel={_='channel', creator=Bool, kicked=Bool, left=Bool, editor=Bool, moderator=Bool, broadcast=Bool, verified=Bool, megagroup=Bool, restricted=Bool, id=int, access_hash=long, title='string', username='string', photo=ChatPhoto, date=int, version=int, restriction_reason='string'}

```


