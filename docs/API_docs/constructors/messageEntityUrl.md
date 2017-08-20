---
title: messageEntityUrl
description: Url beginning with http
---
## Constructor: messageEntityUrl  
[Back to constructors index](index.md)



Url beginning with http

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityUrl = ['_' => 'messageEntityUrl', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityUrl", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityUrl={_='messageEntityUrl', offset=int, length=int}

```


