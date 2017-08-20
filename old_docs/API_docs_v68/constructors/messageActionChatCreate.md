---
title: messageActionChatCreate
description: messageActionChatCreate attributes, type and example
---
## Constructor: messageActionChatCreate  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[string](../types/string.md) | Yes|
|users|Array of [int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChatCreate = ['_' => 'messageActionChatCreate', 'title' => 'string', 'users' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChatCreate", "title": "string", "users": [int]}
```


Or, if you're into Lua:  


```
messageActionChatCreate={_='messageActionChatCreate', title='string', users={int}}

```


