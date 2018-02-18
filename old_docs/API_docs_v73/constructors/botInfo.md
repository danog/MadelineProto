---
title: botInfo
description: botInfo attributes, type and example
---
## Constructor: botInfo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|description|[string](../types/string.md) | Yes|
|commands|Array of [BotCommand](../types/BotCommand.md) | Yes|



### Type: [BotInfo](../types/BotInfo.md)


### Example:

```
$botInfo = ['_' => 'botInfo', 'user_id' => int, 'description' => 'string', 'commands' => [BotCommand]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInfo", "user_id": int, "description": "string", "commands": [BotCommand]}
```


Or, if you're into Lua:  


```
botInfo={_='botInfo', user_id=int, description='string', commands={BotCommand}}

```


