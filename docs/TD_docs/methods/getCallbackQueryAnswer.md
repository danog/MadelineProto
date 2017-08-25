---
title: getCallbackQueryAnswer
description: Sends callback query to a bot and returns answer to it. Unavailable for bots
---
## Method: getCallbackQueryAnswer  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends callback query to a bot and returns answer to it. Unavailable for bots

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat with a message|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message, from which the query is originated|
|payload|[CallbackQueryPayload](../types/CallbackQueryPayload.md) | Yes|Query payload|


### Return type: [CallbackQueryAnswer](../types/CallbackQueryAnswer.md)

