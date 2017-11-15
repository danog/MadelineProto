---
title: updateEncryption
description: updateEncryption attributes, type and example
---
## Constructor: updateEncryption  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat|[EncryptedChat](../types/EncryptedChat.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateEncryption = ['_' => 'updateEncryption', 'chat' => EncryptedChat, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateEncryption", "chat": EncryptedChat, "date": int}
```


Or, if you're into Lua:  


```
updateEncryption={_='updateEncryption', chat=EncryptedChat, date=int}

```


