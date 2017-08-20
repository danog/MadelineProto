---
title: decryptedMessageActionAcceptKey
description: decryptedMessageActionAcceptKey attributes, type and example
---
## Constructor: decryptedMessageActionAcceptKey\_20  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|exchange\_id|[long](../types/long.md) | Yes|
|g\_b|[bytes](../types/bytes.md) | Yes|
|key\_fingerprint|[long](../types/long.md) | Yes|



### Type: [DecryptedMessageAction](../types/DecryptedMessageAction.md)


### Example:

```
$decryptedMessageActionAcceptKey_20 = ['_' => 'decryptedMessageActionAcceptKey', 'exchange_id' => long, 'g_b' => 'bytes', 'key_fingerprint' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageActionAcceptKey", "exchange_id": long, "g_b": "bytes", "key_fingerprint": long}
```


Or, if you're into Lua:  


```
decryptedMessageActionAcceptKey_20={_='decryptedMessageActionAcceptKey', exchange_id=long, g_b='bytes', key_fingerprint=long}

```


