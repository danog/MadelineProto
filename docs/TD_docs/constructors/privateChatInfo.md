---
title: privateChatInfo
description: Ordinary chat with one user
---
## Constructor: privateChatInfo  
[Back to constructors index](index.md)



Ordinary chat with one user

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user|[user](../types/user.md) | Yes|Information about interlocutor|



### Type: [ChatInfo](../types/ChatInfo.md)


### Example:

```
$privateChatInfo = ['_' => 'privateChatInfo', 'user' => user];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "privateChatInfo", "user": user}
```


Or, if you're into Lua:  


```
privateChatInfo={_='privateChatInfo', user=user}

```


