---
title: updateMessageViews
description: View count of the message has changed
---
## Constructor: updateMessageViews  
[Back to constructors index](index.md)



View count of the message has changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|message\_id|[long](../types/long.md) | Yes|Message identifier|
|views|[int](../types/int.md) | Yes|New value of view count|



### Type: [Update](../types/Update.md)


### Example:

```
$updateMessageViews = ['_' => 'updateMessageViews', 'chat_id' => long, 'message_id' => long, 'views' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateMessageViews", "chat_id": long, "message_id": long, "views": int}
```


Or, if you're into Lua:  


```
updateMessageViews={_='updateMessageViews', chat_id=long, message_id=long, views=int}

```


