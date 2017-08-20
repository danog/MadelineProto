---
title: inputEncryptedChat
description: inputEncryptedChat attributes, type and example
---
## Constructor: inputEncryptedChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputEncryptedChat](../types/InputEncryptedChat.md)


### Example:

```
$inputEncryptedChat = ['_' => 'inputEncryptedChat', 'chat_id' => int, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputEncryptedChat", "chat_id": int, "access_hash": long}
```


Or, if you're into Lua:  


```
inputEncryptedChat={_='inputEncryptedChat', chat_id=int, access_hash=long}

```


