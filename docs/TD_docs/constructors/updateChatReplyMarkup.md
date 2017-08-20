---
title: updateChatReplyMarkup
description: Default chat reply markup has changed. It can happen because new message with reply markup has come or old reply markup was hidden by user
---
## Constructor: updateChatReplyMarkup  
[Back to constructors index](index.md)



Default chat reply markup has changed. It can happen because new message with reply markup has come or old reply markup was hidden by user

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|reply\_markup\_message\_id|[long](../types/long.md) | Yes|Identifier of the message from which reply markup need to be used or 0 if there is no default custom reply markup in the chat|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatReplyMarkup = ['_' => 'updateChatReplyMarkup', 'chat_id' => long, 'reply_markup_message_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatReplyMarkup", "chat_id": long, "reply_markup_message_id": long}
```


Or, if you're into Lua:  


```
updateChatReplyMarkup={_='updateChatReplyMarkup', chat_id=long, reply_markup_message_id=long}

```


