---
title: messages_highScores
description: messages_highScores attributes, type and example
---
## Constructor: messages\_highScores  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|scores|Array of [HighScore](../types/HighScore.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [messages\_HighScores](../types/messages_HighScores.md)


### Example:

```
$messages_highScores = ['_' => 'messages_highScores', 'scores' => [Vector t], 'users' => [Vector t], ];
```