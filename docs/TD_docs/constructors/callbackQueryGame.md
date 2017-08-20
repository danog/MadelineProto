---
title: callbackQueryGame
description: Payload from a game callback button
---
## Constructor: callbackQueryGame  
[Back to constructors index](index.md)



Payload from a game callback button

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|game\_short\_name|[string](../types/string.md) | Yes|Short name of the game that was attached to the callback button|



### Type: [CallbackQueryPayload](../types/CallbackQueryPayload.md)


### Example:

```
$callbackQueryGame = ['_' => 'callbackQueryGame', 'game_short_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "callbackQueryGame", "game_short_name": "string"}
```


Or, if you're into Lua:  


```
callbackQueryGame={_='callbackQueryGame', game_short_name='string'}

```


