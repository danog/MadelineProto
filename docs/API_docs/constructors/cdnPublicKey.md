---
title: cdnPublicKey
description: cdnPublicKey attributes, type and example
---
## Constructor: cdnPublicKey  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|dc\_id|[int](../types/int.md) | Yes|
|public\_key|[string](../types/string.md) | Yes|



### Type: [CdnPublicKey](../types/CdnPublicKey.md)


### Example:

```
$cdnPublicKey = ['_' => 'cdnPublicKey', 'dc_id' => int, 'public_key' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "cdnPublicKey", "dc_id": int, "public_key": "string"}
```


Or, if you're into Lua:  


```
cdnPublicKey={_='cdnPublicKey', dc_id=int, public_key='string'}

```


