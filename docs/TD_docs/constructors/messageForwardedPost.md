---
title: messageForwardedPost
description: Message is orifinally a channel post
---
## Constructor: messageForwardedPost  
[Back to constructors index](index.md)



Message is orifinally a channel post

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Identifier of a chat from which message is forwarded|
|sender\_user\_id|[int](../types/int.md) | Yes|User identifier of the original message sender, 0 if unknown|
|date|[int](../types/int.md) | Yes|Date when message was originally sent|
|message\_id|[long](../types/long.md) | Yes|Message identifier of the message from which the message is forwarded, 0 if unknown|



### Type: [MessageForwardInfo](../types/MessageForwardInfo.md)


