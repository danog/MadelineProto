---
title: chatInvite
description: chatInvite attributes, type and example
---
## Constructor: chatInvite  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Bool](../types/Bool.md) | Optional|
|broadcast|[Bool](../types/Bool.md) | Optional|
|public|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|title|[string](../types/string.md) | Yes|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Yes|
|participants\_count|[int](../types/int.md) | Yes|
|participants|Array of [User](../types/User.md) | Optional|



### Type: [ChatInvite](../types/ChatInvite.md)


### Example:

```
$chatInvite = ['_' => 'chatInvite', 'channel' => Bool, 'broadcast' => Bool, 'public' => Bool, 'megagroup' => Bool, 'title' => 'string', 'photo' => ChatPhoto, 'participants_count' => int, 'participants' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatInvite", "channel": Bool, "broadcast": Bool, "public": Bool, "megagroup": Bool, "title": "string", "photo": ChatPhoto, "participants_count": int, "participants": [User]}
```


Or, if you're into Lua:  


```
chatInvite={_='chatInvite', channel=Bool, broadcast=Bool, public=Bool, megagroup=Bool, title='string', photo=ChatPhoto, participants_count=int, participants={User}}

```


