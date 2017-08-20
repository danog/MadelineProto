---
title: encryptedMessage
description: encryptedMessage attributes, type and example
---
## Constructor: encryptedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|decrypted\_message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|
|file|[EncryptedFile](../types/EncryptedFile.md) | Yes|



### Type: [EncryptedMessage](../types/EncryptedMessage.md)


### Example:

```
$encryptedMessage = ['_' => 'encryptedMessage', 'chat_id' => int, 'date' => int, 'decrypted_message' => DecryptedMessage, 'file' => EncryptedFile];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "encryptedMessage", "chat_id": int, "date": int, "decrypted_message": DecryptedMessage, "file": EncryptedFile}
```


Or, if you're into Lua:  


```
encryptedMessage={_='encryptedMessage', chat_id=int, date=int, decrypted_message=DecryptedMessage, file=EncryptedFile}

```


