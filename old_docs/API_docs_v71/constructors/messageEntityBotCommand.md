---
title: messageEntityBotCommand
description: messageEntityBotCommand attributes, type and example
---
## Constructor: messageEntityBotCommand  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityBotCommand = ['_' => 'messageEntityBotCommand', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityBotCommand", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityBotCommand={_='messageEntityBotCommand', offset=int, length=int}

```


