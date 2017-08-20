---
title: updateChatTitle
description: Title of chat was changed
---
## Constructor: updateChatTitle  
[Back to constructors index](index.md)



Title of chat was changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|title|[string](../types/string.md) | Yes|New chat title|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatTitle = ['_' => 'updateChatTitle', 'chat_id' => long, 'title' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatTitle", "chat_id": long, "title": "string"}
```


Or, if you're into Lua:  


```
updateChatTitle={_='updateChatTitle', chat_id=long, title='string'}

```


