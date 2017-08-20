---
title: encryptedMessageService
description: encryptedMessageService attributes, type and example
---
## Constructor: encryptedMessageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|decrypted\_message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|



### Type: [EncryptedMessage](../types/EncryptedMessage.md)


### Example:

```
$encryptedMessageService = ['_' => 'encryptedMessageService', 'chat_id' => int, 'date' => int, 'decrypted_message' => DecryptedMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "encryptedMessageService", "chat_id": int, "date": int, "decrypted_message": DecryptedMessage}
```


Or, if you're into Lua:  


```
encryptedMessageService={_='encryptedMessageService', chat_id=int, date=int, decrypted_message=DecryptedMessage}

```


