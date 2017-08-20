---
title: messageEntityBotCommand
description: Bot command beginning with /
---
## Constructor: messageEntityBotCommand  
[Back to constructors index](index.md)



Bot command beginning with /

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



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


