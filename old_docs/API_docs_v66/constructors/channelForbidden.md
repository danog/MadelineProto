---
title: channelForbidden
description: channelForbidden attributes, type and example
---
## Constructor: channelForbidden  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|broadcast|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|title|[string](../types/string.md) | Yes|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channelForbidden = ['_' => 'channelForbidden', 'broadcast' => Bool, 'megagroup' => Bool, 'id' => int, 'access_hash' => long, 'title' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelForbidden", "broadcast": Bool, "megagroup": Bool, "id": int, "access_hash": long, "title": "string"}
```


Or, if you're into Lua:  


```
channelForbidden={_='channelForbidden', broadcast=Bool, megagroup=Bool, id=int, access_hash=long, title='string'}

```


