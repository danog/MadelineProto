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
|chat\_id|[int53](../types/int53.md) | Yes|Identifier of a chat from which message is forwarded|
|author\_signature|[string](../types/string.md) | Yes|Post author signature|
|date|[int](../types/int.md) | Yes|Date when message was originally sent|
|message\_id|[int53](../types/int53.md) | Yes|Message identifier of the message from which the message is forwarded, 0 if unknown|



### Type: [MessageForwardInfo](../types/MessageForwardInfo.md)


