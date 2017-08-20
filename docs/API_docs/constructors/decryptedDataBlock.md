---
title: decryptedDataBlock
description: decryptedDataBlock attributes, type and example
---
## Constructor: decryptedDataBlock  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|voice\_call\_id|[int128](../types/int128.md) | Optional|
|in\_seq\_no|[int](../types/int.md) | Optional|
|out\_seq\_no|[int](../types/int.md) | Optional|
|recent\_received\_mask|[int](../types/int.md) | Optional|
|proto|[int](../types/int.md) | Optional|
|extra|[string](../types/string.md) | Optional|
|raw\_data|[string](../types/string.md) | Optional|



### Type: [DecryptedDataBlock](../types/DecryptedDataBlock.md)


### Example:

```
$decryptedDataBlock = ['_' => 'decryptedDataBlock', 'voice_call_id' => int128, 'in_seq_no' => int, 'out_seq_no' => int, 'recent_received_mask' => int, 'proto' => int, 'extra' => 'string', 'raw_data' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedDataBlock", "voice_call_id": int128, "in_seq_no": int, "out_seq_no": int, "recent_received_mask": int, "proto": int, "extra": "string", "raw_data": "string"}
```


Or, if you're into Lua:  


```
decryptedDataBlock={_='decryptedDataBlock', voice_call_id=int128, in_seq_no=int, out_seq_no=int, recent_received_mask=int, proto=int, extra='string', raw_data='string'}

```


