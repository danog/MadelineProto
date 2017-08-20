---
title: messageChatMigrateFrom
description: Supergroup channel is created from group chat
---
## Constructor: messageChatMigrateFrom  
[Back to constructors index](index.md)



Supergroup channel is created from group chat

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Title of created channel chat|
|group\_id|[int](../types/int.md) | Yes|Identifier of the group it is migrated from|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChatMigrateFrom = ['_' => 'messageChatMigrateFrom', 'title' => 'string', 'group_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChatMigrateFrom", "title": "string", "group_id": int}
```


Or, if you're into Lua:  


```
messageChatMigrateFrom={_='messageChatMigrateFrom', title='string', group_id=int}

```


