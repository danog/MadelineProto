---
title: updateChat
description: Some date about chat has been changed
---
## Constructor: updateChat  
[Back to constructors index](index.md)



Some date about chat has been changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat|[chat](../types/chat.md) | Yes|New data about the chat|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChat = ['_' => 'updateChat', 'chat' => chat];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChat", "chat": chat}
```


Or, if you're into Lua:  


```
updateChat={_='updateChat', chat=chat}

```


