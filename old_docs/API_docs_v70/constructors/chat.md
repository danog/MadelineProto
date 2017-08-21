---
title: chat
description: chat attributes, type and example
---
## Constructor: chat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|creator|[Bool](../types/Bool.md) | Optional|
|kicked|[Bool](../types/Bool.md) | Optional|
|left|[Bool](../types/Bool.md) | Optional|
|admins\_enabled|[Bool](../types/Bool.md) | Optional|
|admin|[Bool](../types/Bool.md) | Optional|
|deactivated|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|title|[string](../types/string.md) | Yes|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Yes|
|participants\_count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|
|migrated\_to|[InputChannel](../types/InputChannel.md) | Optional|



### Type: [Chat](../types/Chat.md)


### Example:

```
$chat = ['_' => 'chat', 'creator' => Bool, 'kicked' => Bool, 'left' => Bool, 'admins_enabled' => Bool, 'admin' => Bool, 'deactivated' => Bool, 'id' => int, 'title' => 'string', 'photo' => ChatPhoto, 'participants_count' => int, 'date' => int, 'version' => int, 'migrated_to' => InputChannel];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chat", "creator": Bool, "kicked": Bool, "left": Bool, "admins_enabled": Bool, "admin": Bool, "deactivated": Bool, "id": int, "title": "string", "photo": ChatPhoto, "participants_count": int, "date": int, "version": int, "migrated_to": InputChannel}
```


Or, if you're into Lua:  


```
chat={_='chat', creator=Bool, kicked=Bool, left=Bool, admins_enabled=Bool, admin=Bool, deactivated=Bool, id=int, title='string', photo=ChatPhoto, participants_count=int, date=int, version=int, migrated_to=InputChannel}

```


