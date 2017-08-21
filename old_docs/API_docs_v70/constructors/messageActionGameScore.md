---
title: messageActionGameScore
description: messageActionGameScore attributes, type and example
---
## Constructor: messageActionGameScore  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|game\_id|[long](../types/long.md) | Yes|
|score|[int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionGameScore = ['_' => 'messageActionGameScore', 'game_id' => long, 'score' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionGameScore", "game_id": long, "score": int}
```


Or, if you're into Lua:  


```
messageActionGameScore={_='messageActionGameScore', game_id=long, score=int}

```


