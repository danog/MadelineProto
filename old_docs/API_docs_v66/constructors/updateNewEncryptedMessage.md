---
title: updateNewEncryptedMessage
description: updateNewEncryptedMessage attributes, type and example
---
## Constructor: updateNewEncryptedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[EncryptedMessage](../types/EncryptedMessage.md) | Yes|
|qts|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewEncryptedMessage = ['_' => 'updateNewEncryptedMessage', 'message' => EncryptedMessage, 'qts' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewEncryptedMessage", "message": EncryptedMessage, "qts": int}
```


Or, if you're into Lua:  


```
updateNewEncryptedMessage={_='updateNewEncryptedMessage', message=EncryptedMessage, qts=int}

```


