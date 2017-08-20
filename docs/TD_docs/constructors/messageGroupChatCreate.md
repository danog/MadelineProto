---
title: messageGroupChatCreate
description: New group chat created
---
## Constructor: messageGroupChatCreate  
[Back to constructors index](index.md)



New group chat created

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Title of created group chat|
|members|Array of [user](../constructors/user.md) | Yes|Parcticipants of created group chat|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageGroupChatCreate = ['_' => 'messageGroupChatCreate', 'title' => 'string', 'members' => [user]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageGroupChatCreate", "title": "string", "members": [user]}
```


Or, if you're into Lua:  


```
messageGroupChatCreate={_='messageGroupChatCreate', title='string', members={user}}

```


