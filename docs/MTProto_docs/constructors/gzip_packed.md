---
title: gzip_packed
description: gzip_packed attributes, type and example
---
## Constructor: gzip\_packed  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|packed\_data|[bytes](../types/bytes.md) | Yes|



### Type: [Object](../types/Object.md)


### Example:

```
$gzip_packed = ['_' => 'gzip_packed', 'packed_data' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "gzip_packed", "packed_data": "bytes"}
```


Or, if you're into Lua:  


```
gzip_packed={_='gzip_packed', packed_data='bytes'}

```


