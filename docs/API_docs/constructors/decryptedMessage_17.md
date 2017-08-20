---
title: decryptedMessage
description: decryptedMessage attributes, type and example
---
## Constructor: decryptedMessage\_17  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ttl|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[DecryptedMessageMedia](../types/DecryptedMessageMedia.md) | Yes|



### Type: [DecryptedMessage](../types/DecryptedMessage.md)


### Example:

```
$decryptedMessage_17 = ['_' => 'decryptedMessage', 'ttl' => int, 'message' => 'string', 'media' => DecryptedMessageMedia];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessage", "ttl": int, "message": "string", "media": DecryptedMessageMedia}
```


Or, if you're into Lua:  


```
decryptedMessage_17={_='decryptedMessage', ttl=int, message='string', media=DecryptedMessageMedia}

```


