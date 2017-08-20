---
title: updateNewCallbackQuery
description: Bots only. New incoming callback query
---
## Constructor: updateNewCallbackQuery  
[Back to constructors index](index.md)



Bots only. New incoming callback query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Unique query identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the query|
|chat\_id|[long](../types/long.md) | Yes|Identifier of the chat, in which the query was sent|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message, from which the query is originated|
|chat\_instance|[long](../types/long.md) | Yes|Identifier, uniquely corresponding to the chat a message was sent to|
|payload|[CallbackQueryPayload](../types/CallbackQueryPayload.md) | Yes|Query payload|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewCallbackQuery = ['_' => 'updateNewCallbackQuery', 'id' => long, 'sender_user_id' => int, 'chat_id' => long, 'message_id' => long, 'chat_instance' => long, 'payload' => CallbackQueryPayload];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewCallbackQuery", "id": long, "sender_user_id": int, "chat_id": long, "message_id": long, "chat_instance": long, "payload": CallbackQueryPayload}
```


Or, if you're into Lua:  


```
updateNewCallbackQuery={_='updateNewCallbackQuery', id=long, sender_user_id=int, chat_id=long, message_id=long, chat_instance=long, payload=CallbackQueryPayload}

```


