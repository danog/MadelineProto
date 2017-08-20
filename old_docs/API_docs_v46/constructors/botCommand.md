---
title: botCommand
description: botCommand attributes, type and example
---
## Constructor: botCommand  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|command|[string](../types/string.md) | Yes|
|description|[string](../types/string.md) | Yes|



### Type: [BotCommand](../types/BotCommand.md)


### Example:

```
$botCommand = ['_' => 'botCommand', 'command' => 'string', 'description' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botCommand", "command": "string", "description": "string"}
```


Or, if you're into Lua:  


```
botCommand={_='botCommand', command='string', description='string'}

```


