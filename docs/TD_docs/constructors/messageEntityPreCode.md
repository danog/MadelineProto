---
title: messageEntityPreCode
description: Text needs to be formatted as inside of pre and code HTML tags
---
## Constructor: messageEntityPreCode  
[Back to constructors index](index.md)



Text needs to be formatted as inside of pre and code HTML tags

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|
|language|[string](../types/string.md) | Yes|Language of code as defined by sender|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityPreCode = ['_' => 'messageEntityPreCode', 'offset' => int, 'length' => int, 'language' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityPreCode", "offset": int, "length": int, "language": "string"}
```


Or, if you're into Lua:  


```
messageEntityPreCode={_='messageEntityPreCode', offset=int, length=int, language='string'}

```


