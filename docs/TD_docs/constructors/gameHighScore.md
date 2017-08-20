---
title: gameHighScore
description: Contains one row of the game high scores table
---
## Constructor: gameHighScore  
[Back to constructors index](index.md)



Contains one row of the game high scores table

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|position|[int](../types/int.md) | Yes|Position in the high score table|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|score|[int](../types/int.md) | Yes|User score|



### Type: [GameHighScore](../types/GameHighScore.md)


### Example:

```
$gameHighScore = ['_' => 'gameHighScore', 'position' => int, 'user_id' => int, 'score' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "gameHighScore", "position": int, "user_id": int, "score": int}
```


Or, if you're into Lua:  


```
gameHighScore={_='gameHighScore', position=int, user_id=int, score=int}

```


