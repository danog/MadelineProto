---
title: messageActionChannelMigrateFrom
description: messageActionChannelMigrateFrom attributes, type and example
---
## Constructor: messageActionChannelMigrateFrom  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[string](../types/string.md) | Yes|
|chat\_id|[int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChannelMigrateFrom = ['_' => 'messageActionChannelMigrateFrom', 'title' => 'string', 'chat_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChannelMigrateFrom", "title": "string", "chat_id": int}
```


Or, if you're into Lua:  


```
messageActionChannelMigrateFrom={_='messageActionChannelMigrateFrom', title='string', chat_id=int}

```


