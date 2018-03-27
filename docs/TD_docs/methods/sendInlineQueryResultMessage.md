---
title: sendInlineQueryResultMessage
description: Sends result of the inline query as a message. Returns sent message. Always clears chat draft message
---
## Method: sendInlineQueryResultMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends result of the inline query as a message. Returns sent message. Always clears chat draft message

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat to send message|
|reply\_to\_message\_id|[int53](../types/int53.md) | Yes|Identifier of a message to reply to or 0|
|disable\_notification|[Bool](../types/Bool.md) | Yes|Pass true, to disable notification about the message, doesn't works in secret chats|
|from\_background|[Bool](../types/Bool.md) | Yes|Pass true, if the message is sent from background|
|query\_id|[int64](../constructors/int64.md) | Yes|Identifier of the inline query|
|result\_id|[string](../types/string.md) | Yes|Identifier of the inline result|


### Return type: [Message](../types/Message.md)

