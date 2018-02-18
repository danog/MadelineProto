---
title: channel
description: channel attributes, type and example
---
## Constructor: channel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|title|[string](../types/string.md) | Yes|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Optional|
|date|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => 'channel', 'id' => int, 'access_hash' => long, 'title' => 'string', 'photo' => ChatPhoto, 'date' => int, 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channel", "id": int, "access_hash": long, "title": "string", "photo": ChatPhoto, "date": int, "version": int}
```


Or, if you're into Lua:  


```
channel={_='channel', id=int, access_hash=long, title='string', photo=ChatPhoto, date=int, version=int}

```


