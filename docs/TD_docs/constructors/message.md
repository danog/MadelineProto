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
|id|[long](../types/long.md) | Yes|Unique message identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the message, 0 if unknown. It can be unknown for channel posts which are not signed by the author|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|send\_state|[MessageSendState](../types/MessageSendState.md) | Yes|Information about sending state of the message|
|can\_be\_edited|[Bool](../types/Bool.md) | Yes|True, if message can be edited|
|can\_be\_deleted|[Bool](../types/Bool.md) | Yes|True, if message can be deleted|
|is\_post|[Bool](../types/Bool.md) | Yes|True, if message is channel post. All messages to broadcast channels are posts, all other messages are not posts|
|date|[int](../types/int.md) | Yes|Date when message was sent, unix time|
|edit\_date|[int](../types/int.md) | Yes|Date when message was edited last time, unix time|
|forward\_info|[MessageForwardInfo](../types/MessageForwardInfo.md) | Yes|Information about initial message sender, nullable|
|reply\_to\_message\_id|[long](../types/long.md) | Yes|If non-zero, identifier of the message this message replies to, can be identifier of deleted message|
|ttl|[int](../types/int.md) | Yes|Message TTL for messages in secret chats in seconds, 0 if none. TDLib will send updateDeleteMessages when TTL expires|
|ttl\_expires\_in|[double](../types/double.md) | Yes|Time left for message ttl to expire in seconds|
|via\_bot\_user\_id|[int](../types/int.md) | Yes|If non-zero, user identifier of the bot this message is sent via|
|views|[int](../types/int.md) | Yes|Number of times this message was viewed|
|content|[MessageContent](../types/MessageContent.md) | Yes|Content of the message|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Reply markup for the message, nullable|



### Type: [Message](../types/Message.md)


### Example:

```
$message = ['_' => 'message', 'id' => long, 'sender_user_id' => int, 'chat_id' => long, 'send_state' => MessageSendState, 'can_be_edited' => Bool, 'can_be_deleted' => Bool, 'is_post' => Bool, 'date' => int, 'edit_date' => int, 'forward_info' => MessageForwardInfo, 'reply_to_message_id' => long, 'ttl' => int, 'ttl_expires_in' => double, 'via_bot_user_id' => int, 'views' => int, 'content' => MessageContent, 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "message", "id": long, "sender_user_id": int, "chat_id": long, "send_state": MessageSendState, "can_be_edited": Bool, "can_be_deleted": Bool, "is_post": Bool, "date": int, "edit_date": int, "forward_info": MessageForwardInfo, "reply_to_message_id": long, "ttl": int, "ttl_expires_in": double, "via_bot_user_id": int, "views": int, "content": MessageContent, "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
message={_='message', id=long, sender_user_id=int, chat_id=long, send_state=MessageSendState, can_be_edited=Bool, can_be_deleted=Bool, is_post=Bool, date=int, edit_date=int, forward_info=MessageForwardInfo, reply_to_message_id=long, ttl=int, ttl_expires_in=double, via_bot_user_id=int, views=int, content=MessageContent, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


