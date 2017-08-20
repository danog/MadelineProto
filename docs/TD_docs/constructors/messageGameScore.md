---
title: messageGameScore
description: New high score was achieved in a game
---
## Constructor: messageGameScore  
[Back to constructors index](index.md)



New high score was achieved in a game

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|game\_message\_id|[long](../types/long.md) | Yes|Identifier of the message with the game, can be identifier of the deleted message|
|game\_id|[long](../types/long.md) | Yes|Identifier of the game, may be different from the games presented in the message with the game|
|score|[int](../types/int.md) | Yes|New score|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageGameScore = ['_' => 'messageGameScore', 'game_message_id' => long, 'game_id' => long, 'score' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageGameScore", "game_message_id": long, "game_id": long, "score": int}
```


Or, if you're into Lua:  


```
messageGameScore={_='messageGameScore', game_message_id=long, game_id=long, score=int}

```


