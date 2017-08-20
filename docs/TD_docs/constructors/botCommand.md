---
title: botCommand
description: Represents command supported by bot
---
## Constructor: botCommand  
[Back to constructors index](index.md)



Represents command supported by bot

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|command|[string](../types/string.md) | Yes|Text of the bot command|
|description|[string](../types/string.md) | Yes|Description of the bot command|



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


