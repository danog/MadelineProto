---
title: updateNewInlineCallbackQuery
description: Bots only. New incoming callback query from message sent via bot
---
## Constructor: updateNewInlineCallbackQuery  
[Back to constructors index](index.md)



Bots only. New incoming callback query from message sent via bot

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Unique query identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the query|
|inline\_message\_id|[string](../types/string.md) | Yes|Identifier of the inline message, from which the query is originated|
|chat\_instance|[long](../types/long.md) | Yes|Identifier, uniquely corresponding to the chat a message was sent to|
|payload|[CallbackQueryPayload](../types/CallbackQueryPayload.md) | Yes|Query payload|



### Type: [Update](../types/Update.md)


