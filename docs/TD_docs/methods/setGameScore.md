---
title: setGameScore
description: Bots only. Updates game score of the specified user in the game
---
## Method: setGameScore  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Updates game score of the specified user in the game

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat a message with the game belongs to|
|message\_id|[int53](../types/int53.md) | Yes|Identifier of the message|
|edit\_message|[Bool](../types/Bool.md) | Yes|True, if message should be edited|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|score|[int](../types/int.md) | Yes|New score|
|force|[Bool](../types/Bool.md) | Yes|Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table|


### Return type: [Message](../types/Message.md)

