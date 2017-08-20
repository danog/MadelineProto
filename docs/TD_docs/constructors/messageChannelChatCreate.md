---
title: messageChannelChatCreate
description: New channel chat created
---
## Constructor: messageChannelChatCreate  
[Back to constructors index](index.md)



New channel chat created

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Title of created channel chat|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChannelChatCreate = ['_' => 'messageChannelChatCreate', 'title' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChannelChatCreate", "title": "string"}
```


Or, if you're into Lua:  


```
messageChannelChatCreate={_='messageChannelChatCreate', title='string'}

```


