---
title: decryptedMessageActionAcceptKey20
description: decryptedMessageActionAcceptKey20 attributes, type and example
---
## Constructor: decryptedMessageActionAcceptKey20  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|exchange\_id|[long](../types/long.md) | Yes|
|g\_b|[bytes](../types/bytes.md) | Yes|
|key\_fingerprint|[long](../types/long.md) | Yes|



### Type: [DecryptedMessageAction](../types/DecryptedMessageAction.md)


### Example:

```
$decryptedMessageActionAcceptKey20 = ['_' => 'decryptedMessageActionAcceptKey20', 'exchange_id' => long, 'g_b' => bytes, 'key_fingerprint' => long, ];
```  

Or, if you're into Lua:  


```
decryptedMessageActionAcceptKey20={_='decryptedMessageActionAcceptKey20', exchange_id=long, g_b=bytes, key_fingerprint=long, }

```


