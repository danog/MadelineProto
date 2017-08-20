---
title: messageChatDeleteMember
description: Chat member deleted
---
## Constructor: messageChatDeleteMember  
[Back to constructors index](index.md)



Chat member deleted

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user|[user](../types/user.md) | Yes|Left or kicked chat member|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChatDeleteMember = ['_' => 'messageChatDeleteMember', 'user' => user];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChatDeleteMember", "user": user}
```


Or, if you're into Lua:  


```
messageChatDeleteMember={_='messageChatDeleteMember', user=user}

```


