---
title: messageEntityEmail
description: Email
---
## Constructor: messageEntityEmail  
[Back to constructors index](index.md)



Email

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityEmail = ['_' => 'messageEntityEmail', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityEmail", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityEmail={_='messageEntityEmail', offset=int, length=int}

```


