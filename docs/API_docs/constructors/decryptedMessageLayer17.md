---
title: decryptedMessageLayer17
description: decryptedMessageLayer17 attributes, type and example
---
## Constructor: decryptedMessageLayer17  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|layer|[int](../types/int.md) | Yes|
|in\_seq\_no|[int](../types/int.md) | Yes|
|out\_seq\_no|[int](../types/int.md) | Yes|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|



### Type: [DecryptedMessageLayer](../types/DecryptedMessageLayer.md)


### Example:

```
$decryptedMessageLayer17 = ['_' => 'decryptedMessageLayer17', 'layer' => int, 'in_seq_no' => int, 'out_seq_no' => int, 'message' => DecryptedMessage, ];
```  

Or, if you're into Lua:  


```
decryptedMessageLayer17={_='decryptedMessageLayer17', layer=int, in_seq_no=int, out_seq_no=int, message=DecryptedMessage, }

```


