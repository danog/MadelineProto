---
title: messageEntityEmail
description: messageEntityEmail attributes, type and example
---
## Constructor: messageEntityEmail  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



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


