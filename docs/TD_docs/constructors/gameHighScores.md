---
title: gameHighScores
description: Contains list of game high scores
---
## Constructor: gameHighScores  
[Back to constructors index](index.md)



Contains list of game high scores

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|scores|Array of [gameHighScore](../constructors/gameHighScore.md) | Yes|List of game high scores|



### Type: [GameHighScores](../types/GameHighScores.md)


### Example:

```
$gameHighScores = ['_' => 'gameHighScores', 'scores' => [gameHighScore]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "gameHighScores", "scores": [gameHighScore]}
```


Or, if you're into Lua:  


```
gameHighScores={_='gameHighScores', scores={gameHighScore}}

```


