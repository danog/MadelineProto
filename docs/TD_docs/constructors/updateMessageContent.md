---
title: updateMessageContent
description: Sent message gets new content
---
## Constructor: updateMessageContent  
[Back to constructors index](index.md)



Sent message gets new content

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|message\_id|[long](../types/long.md) | Yes|Message identifier|
|new\_content|[MessageContent](../types/MessageContent.md) | Yes|New message content|



### Type: [Update](../types/Update.md)


### Example:

```
$updateMessageContent = ['_' => 'updateMessageContent', 'chat_id' => long, 'message_id' => long, 'new_content' => MessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateMessageContent", "chat_id": long, "message_id": long, "new_content": MessageContent}
```


Or, if you're into Lua:  


```
updateMessageContent={_='updateMessageContent', chat_id=long, message_id=long, new_content=MessageContent}

```


