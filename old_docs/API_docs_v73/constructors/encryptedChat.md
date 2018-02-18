---
title: encryptedChat
description: encryptedChat attributes, type and example
---
## Constructor: encryptedChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|admin\_id|[int](../types/int.md) | Yes|
|participant\_id|[int](../types/int.md) | Yes|
|g\_a\_or\_b|[bytes](../types/bytes.md) | Yes|
|key\_fingerprint|[long](../types/long.md) | Yes|



### Type: [EncryptedChat](../types/EncryptedChat.md)


### Example:

```
$encryptedChat = ['_' => 'encryptedChat', 'id' => int, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a_or_b' => 'bytes', 'key_fingerprint' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "encryptedChat", "id": int, "access_hash": long, "date": int, "admin_id": int, "participant_id": int, "g_a_or_b": "bytes", "key_fingerprint": long}
```


Or, if you're into Lua:  


```
encryptedChat={_='encryptedChat', id=int, access_hash=long, date=int, admin_id=int, participant_id=int, g_a_or_b='bytes', key_fingerprint=long}

```


