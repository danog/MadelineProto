---
title: updateNewEncryptedMessage
description: updateNewEncryptedMessage attributes, type and example
---
## Constructor: updateNewEncryptedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|encr\_message|[EncryptedMessage](../types/EncryptedMessage.md) | Yes|
|qts|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewEncryptedMessage = ['_' => 'updateNewEncryptedMessage', 'encr_message' => EncryptedMessage, 'qts' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewEncryptedMessage", "encr_message": EncryptedMessage, "qts": int}
```


Or, if you're into Lua:  


```
updateNewEncryptedMessage={_='updateNewEncryptedMessage', encr_message=EncryptedMessage, qts=int}

```


