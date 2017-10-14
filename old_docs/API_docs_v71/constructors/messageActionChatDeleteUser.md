---
title: messageActionChatDeleteUser
description: messageActionChatDeleteUser attributes, type and example
---
## Constructor: messageActionChatDeleteUser  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChatDeleteUser = ['_' => 'messageActionChatDeleteUser', 'user_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChatDeleteUser", "user_id": int}
```


Or, if you're into Lua:  


```
messageActionChatDeleteUser={_='messageActionChatDeleteUser', user_id=int}

```


