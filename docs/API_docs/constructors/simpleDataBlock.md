---
title: simpleDataBlock
description: simpleDataBlock attributes, type and example
---
## Constructor: simpleDataBlock  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|raw\_data|[string](../types/string.md) | Yes|



### Type: [DecryptedDataBlock](../types/DecryptedDataBlock.md)


### Example:

```
$simpleDataBlock = ['_' => 'simpleDataBlock', 'raw_data' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "simpleDataBlock", "raw_data": "string"}
```


Or, if you're into Lua:  


```
simpleDataBlock={_='simpleDataBlock', raw_data='string'}

```


