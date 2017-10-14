---
title: updateDcOptions
description: updateDcOptions attributes, type and example
---
## Constructor: updateDcOptions  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|dc\_options|Array of [DcOption](../types/DcOption.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDcOptions = ['_' => 'updateDcOptions', 'dc_options' => [DcOption]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateDcOptions", "dc_options": [DcOption]}
```


Or, if you're into Lua:  


```
updateDcOptions={_='updateDcOptions', dc_options={DcOption}}

```


