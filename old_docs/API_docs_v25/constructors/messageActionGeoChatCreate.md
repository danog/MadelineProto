---
title: messageActionGeoChatCreate
description: messageActionGeoChatCreate attributes, type and example
---
## Constructor: messageActionGeoChatCreate  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionGeoChatCreate = ['_' => 'messageActionGeoChatCreate', 'title' => 'string', 'address' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionGeoChatCreate", "title": "string", "address": "string"}
```


Or, if you're into Lua:  


```
messageActionGeoChatCreate={_='messageActionGeoChatCreate', title='string', address='string'}

```


