---
title: messageActionChatAddUser
description: messageActionChatAddUser attributes, type and example
---
## Constructor: messageActionChatAddUser  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChatAddUser = ['_' => 'messageActionChatAddUser', 'user_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChatAddUser", "user_id": int}
```


Or, if you're into Lua:  


```
messageActionChatAddUser={_='messageActionChatAddUser', user_id=int}

```


