---
title: message
description: Describes message
---
## Constructor: message  
[Back to constructors index](index.md)



Describes message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int53](../types/int53.md) | Yes|Unique message identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the message, 0 if unknown. It is unknown for channel posts|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier|
|send\_state|[MessageSendState](../types/MessageSendState.md) | Yes|Information about sending state of the message|
|can\_be\_edited|[Bool](../types/Bool.md) | Yes|True, if message can be edited|
|can\_be\_forwarded|[Bool](../types/Bool.md) | Yes|True, if message can be forwarded|
|can\_be\_deleted\_only\_for\_self|[Bool](../types/Bool.md) | Yes|True, if message can be deleted only for self, other users will continue to see it|
|can\_be\_deleted\_for\_everyone|[Bool](../types/Bool.md) | Yes|True, if message can be deleted for everyone|
|is\_post|[Bool](../types/Bool.md) | Yes|True, if message is channel post. All messages to broadcast channels are posts, all other messages are not posts|
|date|[int](../types/int.md) | Yes|Date when message was sent, unix time|
|edit\_date|[int](../types/int.md) | Yes|Date when message was edited last time, unix time|
|forward\_info|[MessageForwardInfo](../types/MessageForwardInfo.md) | Yes|Information about initial message sender, nullable|
|reply\_to\_message\_id|[int53](../types/int53.md) | Yes|If non-zero, identifier of the message this message replies to, can be identifier of deleted message|
|ttl|[int](../types/int.md) | Yes|Message TTL in seconds, 0 if none. TDLib will send updateDeleteMessages or updateMessageContent when TTL expires|
|ttl\_expires\_in|[double](../types/double.md) | Yes|Time left for message TTL to expire in seconds|
|via\_bot\_user\_id|[int](../types/int.md) | Yes|If non-zero, user identifier of the bot this message is sent via|
|author\_signature|[string](../types/string.md) | Yes|For channel posts, optional author signature|
|views|[int](../types/int.md) | Yes|Number of times this message was viewed|
|content|[MessageContent](../types/MessageContent.md) | Yes|Content of the message|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Reply markup for the message, nullable|



### Type: [Message](../types/Message.md)


