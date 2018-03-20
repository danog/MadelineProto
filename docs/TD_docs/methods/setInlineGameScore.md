---
title: setInlineGameScore
description: Bots only. Updates game score of the specified user in the game
---
## Method: setInlineGameScore  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Updates game score of the specified user in the game

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|inline\_message\_id|[CLICK ME string](../types/string.md) | Yes|Inline message identifier|
|edit\_message|[CLICK ME Bool](../types/Bool.md) | Yes|True, if message should be edited|
|user\_id|[CLICK ME int](../types/int.md) | Yes|User identifier|
|score|[CLICK ME int](../types/int.md) | Yes|New score|
|force|[CLICK ME Bool](../types/Bool.md) | Yes|Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table|


### Return type: [Ok](../types/Ok.md)

