---
title: messageChatChangeTitle
description: Chat title changed
---
## Constructor: messageChatChangeTitle  
[Back to constructors index](index.md)



Chat title changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|New chat title|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChatChangeTitle = ['_' => 'messageChatChangeTitle', 'title' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChatChangeTitle", "title": "string"}
```


Or, if you're into Lua:  


```
messageChatChangeTitle={_='messageChatChangeTitle', title='string'}

```


