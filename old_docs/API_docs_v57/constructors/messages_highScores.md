---
title: messages.highScores
description: messages_highScores attributes, type and example
---
## Constructor: messages.highScores  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|scores|Array of [HighScore](../types/HighScore.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [messages\_HighScores](../types/messages_HighScores.md)


### Example:

```
$messages_highScores = ['_' => 'messages.highScores', 'scores' => [HighScore], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.highScores", "scores": [HighScore], "users": [User]}
```


Or, if you're into Lua:  


```
messages_highScores={_='messages.highScores', scores={HighScore}, users={User}}

```


