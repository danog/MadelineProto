---
title: encryptedChatWaiting
description: encryptedChatWaiting attributes, type and example
---
## Constructor: encryptedChatWaiting  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|admin\_id|[int](../types/int.md) | Yes|
|participant\_id|[int](../types/int.md) | Yes|



### Type: [EncryptedChat](../types/EncryptedChat.md)


### Example:

```
$encryptedChatWaiting = ['_' => 'encryptedChatWaiting', 'id' => int, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "encryptedChatWaiting", "id": int, "access_hash": long, "date": int, "admin_id": int, "participant_id": int}
```


Or, if you're into Lua:  


```
encryptedChatWaiting={_='encryptedChatWaiting', id=int, access_hash=long, date=int, admin_id=int, participant_id=int}

```


