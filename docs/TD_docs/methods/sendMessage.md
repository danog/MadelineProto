---
title: sendMessage
description: Sends a message. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message
---
## Method: sendMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends a message. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat to send message|
|reply\_to\_message\_id|[int53](../types/int53.md) | Yes|Identifier of a message to reply to or 0|
|disable\_notification|[Bool](../types/Bool.md) | Yes|Pass true, to disable notification about the message, doesn't works in secret chats|
|from\_background|[Bool](../types/Bool.md) | Yes|Pass true, if the message is sent from background|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Bots only. Markup for replying to message|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of a message to send|


### Return type: [Message](../types/Message.md)

