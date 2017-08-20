---
title: updateMessageEdited
description: Message was edited. Changes in the message content will come in a separate updateMessageContent
---
## Constructor: updateMessageEdited  
[Back to constructors index](index.md)



Message was edited. Changes in the message content will come in a separate updateMessageContent

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|message\_id|[long](../types/long.md) | Yes|Message identifier|
|edit\_date|[int](../types/int.md) | Yes|Date the message was edited, unix time|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|New message reply markup, nullable|



### Type: [Update](../types/Update.md)


### Example:

```
$updateMessageEdited = ['_' => 'updateMessageEdited', 'chat_id' => long, 'message_id' => long, 'edit_date' => int, 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateMessageEdited", "chat_id": long, "message_id": long, "edit_date": int, "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
updateMessageEdited={_='updateMessageEdited', chat_id=long, message_id=long, edit_date=int, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


