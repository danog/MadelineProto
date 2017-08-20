---
title: updateChatOrder
description: Order of the chat in the chat list has changed
---
## Constructor: updateChatOrder  
[Back to constructors index](index.md)



Order of the chat in the chat list has changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|order|[long](../types/long.md) | Yes|New value of the order|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatOrder = ['_' => 'updateChatOrder', 'chat_id' => long, 'order' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatOrder", "chat_id": long, "order": long}
```


Or, if you're into Lua:  


```
updateChatOrder={_='updateChatOrder', chat_id=long, order=long}

```


