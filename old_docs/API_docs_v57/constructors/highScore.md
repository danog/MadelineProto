---
title: highScore
description: highScore attributes, type and example
---
## Constructor: highScore  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pos|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|score|[int](../types/int.md) | Yes|



### Type: [HighScore](../types/HighScore.md)


### Example:

```
$highScore = ['_' => 'highScore', 'pos' => int, 'user_id' => int, 'score' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "highScore", "pos": int, "user_id": int, "score": int}
```


Or, if you're into Lua:  


```
highScore={_='highScore', pos=int, user_id=int, score=int}

```


