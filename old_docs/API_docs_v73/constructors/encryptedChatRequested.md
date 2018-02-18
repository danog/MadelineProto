---
title: encryptedChatRequested
description: encryptedChatRequested attributes, type and example
---
## Constructor: encryptedChatRequested  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|admin\_id|[int](../types/int.md) | Yes|
|participant\_id|[int](../types/int.md) | Yes|
|g\_a|[bytes](../types/bytes.md) | Yes|



### Type: [EncryptedChat](../types/EncryptedChat.md)


### Example:

```
$encryptedChatRequested = ['_' => 'encryptedChatRequested', 'id' => int, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "encryptedChatRequested", "id": int, "access_hash": long, "date": int, "admin_id": int, "participant_id": int, "g_a": "bytes"}
```


Or, if you're into Lua:  


```
encryptedChatRequested={_='encryptedChatRequested', id=int, access_hash=long, date=int, admin_id=int, participant_id=int, g_a='bytes'}

```


