---
title: messageEntityMention
description: Mention of the user by his username
---
## Constructor: messageEntityMention  
[Back to constructors index](index.md)



Mention of the user by his username

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityMention = ['_' => 'messageEntityMention', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityMention", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityMention={_='messageEntityMention', offset=int, length=int}

```


