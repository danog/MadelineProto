---
title: cdnConfig
description: cdnConfig attributes, type and example
---
## Constructor: cdnConfig  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|public\_keys|Array of [CdnPublicKey](../types/CdnPublicKey.md) | Yes|



### Type: [CdnConfig](../types/CdnConfig.md)


### Example:

```
$cdnConfig = ['_' => 'cdnConfig', 'public_keys' => [CdnPublicKey]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "cdnConfig", "public_keys": [CdnPublicKey]}
```


Or, if you're into Lua:  


```
cdnConfig={_='cdnConfig', public_keys={CdnPublicKey}}

```


