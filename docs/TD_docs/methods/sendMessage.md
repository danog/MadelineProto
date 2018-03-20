---
title: sendMessage
description: Sends a message. Returns sent message
---
## Method: sendMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends a message. Returns sent message

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[CLICK ME int53](../types/int53.md) | Yes|Chat to send message|
|reply\_to\_message\_id|[CLICK ME int53](../types/int53.md) | Yes|Identifier of a message to reply to or 0|
|disable\_notification|[CLICK ME Bool](../types/Bool.md) | Yes|Pass true, to disable notification about the message, doesn't works in secret chats|
|from\_background|[CLICK ME Bool](../types/Bool.md) | Yes|Pass true, if the message is sent from background|
|reply\_markup|[CLICK ME ReplyMarkup](../types/ReplyMarkup.md) | Yes|Bots only. Markup for replying to message|
|input\_message\_content|[CLICK ME InputMessageContent](../types/InputMessageContent.md) | Yes|Content of a message to send|


### Return type: [Message](../types/Message.md)

