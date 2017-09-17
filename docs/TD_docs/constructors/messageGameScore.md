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
|game\_message\_id|[int53](../types/int53.md) | Yes|Identifier of the message with the game, can be identifier of the deleted message|
|game\_id|[long](../types/long.md) | Yes|Identifier of the game, may be different from the games presented in the message with the game|
|score|[int](../types/int.md) | Yes|New score|



### Type: [MessageContent](../types/MessageContent.md)


