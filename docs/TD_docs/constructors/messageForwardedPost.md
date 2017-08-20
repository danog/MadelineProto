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


### Example:

```
$messageForwardedPost = ['_' => 'messageForwardedPost', 'chat_id' => long, 'sender_user_id' => int, 'date' => int, 'message_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageForwardedPost", "chat_id": long, "sender_user_id": int, "date": int, "message_id": long}
```


Or, if you're into Lua:  


```
messageForwardedPost={_='messageForwardedPost', chat_id=long, sender_user_id=int, date=int, message_id=long}

```


