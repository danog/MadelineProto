---
title: messageGame
description: Message with a game
---
## Constructor: messageGame  
[Back to constructors index](index.md)



Message with a game

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|game|[game](../types/game.md) | Yes|The game|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageGame = ['_' => 'messageGame', 'game' => game];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageGame", "game": game}
```


Or, if you're into Lua:  


```
messageGame={_='messageGame', game=game}

```


