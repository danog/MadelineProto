---
title: messageActionChatJoinedByLink
description: messageActionChatJoinedByLink attributes, type and example
---
## Constructor: messageActionChatJoinedByLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|inviter\_id|[int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChatJoinedByLink = ['_' => 'messageActionChatJoinedByLink', 'inviter_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChatJoinedByLink", "inviter_id": int}
```


Or, if you're into Lua:  


```
messageActionChatJoinedByLink={_='messageActionChatJoinedByLink', inviter_id=int}

```


