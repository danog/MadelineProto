---
title: messageActionChatMigrateTo
description: messageActionChatMigrateTo attributes, type and example
---
## Constructor: messageActionChatMigrateTo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChatMigrateTo = ['_' => 'messageActionChatMigrateTo', 'channel_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChatMigrateTo", "channel_id": int}
```


Or, if you're into Lua:  


```
messageActionChatMigrateTo={_='messageActionChatMigrateTo', channel_id=int}

```


