---
title: messageChatAddMembers
description: Chat members added
---
## Constructor: messageChatAddMembers  
[Back to constructors index](index.md)



Chat members added

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|members|Array of [user](../constructors/user.md) | Yes|New chat member|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChatAddMembers = ['_' => 'messageChatAddMembers', 'members' => [user]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChatAddMembers", "members": [user]}
```


Or, if you're into Lua:  


```
messageChatAddMembers={_='messageChatAddMembers', members={user}}

```


