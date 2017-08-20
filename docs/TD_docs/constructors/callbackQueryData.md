---
title: callbackQueryData
description: Payload from a general callback button
---
## Constructor: callbackQueryData  
[Back to constructors index](index.md)



Payload from a general callback button

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|data|[bytes](../types/bytes.md) | Yes|Data that was attached to the callback button as specified by the users client|



### Type: [CallbackQueryPayload](../types/CallbackQueryPayload.md)


### Example:

```
$callbackQueryData = ['_' => 'callbackQueryData', 'data' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "callbackQueryData", "data": "bytes"}
```


Or, if you're into Lua:  


```
callbackQueryData={_='callbackQueryData', data='bytes'}

```


