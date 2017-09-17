---
title: forwardMessages
description: Forwards previously sent messages. Returns forwarded messages in the same order as message identifiers passed in message_ids. If message can't be forwarded, null will be returned instead of the message. UpdateChatTopMessage will not be sent, so returned messages should be used to update chat top message
---
## Method: forwardMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Forwards previously sent messages. Returns forwarded messages in the same order as message identifiers passed in message_ids. If message can't be forwarded, null will be returned instead of the message. UpdateChatTopMessage will not be sent, so returned messages should be used to update chat top message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of a chat to forward messages|
|from\_chat\_id|[int53](../types/int53.md) | Yes|Identifier of a chat to forward from|
|message\_ids|Array of [int53](../types/int53.md) | Yes|Identifiers of messages to forward|
|disable\_notification|[Bool](../types/Bool.md) | Yes|Pass true, to disable notification about the message, doesn't works if messages are forwarded to secret chat|
|from\_background|[Bool](../types/Bool.md) | Yes|Pass true, if the message is sent from background|


### Return type: [Messages](../types/Messages.md)

