---
title: messageEntityHashtag
description: Hashtag beginning with #
---
## Constructor: messageEntityHashtag  
[Back to constructors index](index.md)



Hashtag beginning with #

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityHashtag = ['_' => 'messageEntityHashtag', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityHashtag", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityHashtag={_='messageEntityHashtag', offset=int, length=int}

```


