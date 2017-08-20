---
title: inlineQueryResultGame
description: Represents information about a game
---
## Constructor: inlineQueryResultGame  
[Back to constructors index](index.md)



Represents information about a game

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|game|[game](../types/game.md) | Yes|The result|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultGame = ['_' => 'inlineQueryResultGame', 'id' => 'string', 'game' => game];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultGame", "id": "string", "game": game}
```


Or, if you're into Lua:  


```
inlineQueryResultGame={_='inlineQueryResultGame', id='string', game=game}

```


