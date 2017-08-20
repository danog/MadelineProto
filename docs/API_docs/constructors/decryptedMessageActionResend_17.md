---
title: decryptedMessageActionResend
description: decryptedMessageActionResend attributes, type and example
---
## Constructor: decryptedMessageActionResend\_17  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|start\_seq\_no|[int](../types/int.md) | Yes|
|end\_seq\_no|[int](../types/int.md) | Yes|



### Type: [DecryptedMessageAction](../types/DecryptedMessageAction.md)


### Example:

```
$decryptedMessageActionResend_17 = ['_' => 'decryptedMessageActionResend', 'start_seq_no' => int, 'end_seq_no' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageActionResend", "start_seq_no": int, "end_seq_no": int}
```


Or, if you're into Lua:  


```
decryptedMessageActionResend_17={_='decryptedMessageActionResend', start_seq_no=int, end_seq_no=int}

```


