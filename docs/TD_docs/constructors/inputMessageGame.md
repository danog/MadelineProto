---
title: inputMessageGame
description: Message with a game, can't be used in broadcast channels and secret chats
---
## Constructor: inputMessageGame  
[Back to constructors index](index.md)



Message with a game, can't be used in broadcast channels and secret chats

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|bot\_user\_id|[int](../types/int.md) | Yes|User identifier of a bot owned the game|
|game\_short\_name|[string](../types/string.md) | Yes|Game short name|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageGame = ['_' => 'inputMessageGame', 'bot_user_id' => int, 'game_short_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageGame", "bot_user_id": int, "game_short_name": "string"}
```


Or, if you're into Lua:  


```
inputMessageGame={_='inputMessageGame', bot_user_id=int, game_short_name='string'}

```


