---
title: updateChatTopMessage
description: Top message of the chat has changed
---
## Constructor: updateChatTopMessage  
[Back to constructors index](index.md)



Top message of the chat has changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|top\_message|[message](../types/message.md) | Yes|New top message of the chat, nullable|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatTopMessage = ['_' => 'updateChatTopMessage', 'chat_id' => long, 'top_message' => message];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatTopMessage", "chat_id": long, "top_message": message}
```


Or, if you're into Lua:  


```
updateChatTopMessage={_='updateChatTopMessage', chat_id=long, top_message=message}

```


