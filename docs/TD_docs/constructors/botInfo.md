---
title: botInfo
description: Provides information about bot and command supported by him
---
## Constructor: botInfo  
[Back to constructors index](index.md)



Provides information about bot and command supported by him

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|description|[string](../types/string.md) | Yes|Big description shown in user info page|
|commands|Array of [botCommand](../constructors/botCommand.md) | Yes|List of commands cupported by bot|



### Type: [BotInfo](../types/BotInfo.md)


### Example:

```
$botInfo = ['_' => 'botInfo', 'description' => 'string', 'commands' => [botCommand]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInfo", "description": "string", "commands": [botCommand]}
```


Or, if you're into Lua:  


```
botInfo={_='botInfo', description='string', commands={botCommand}}

```


