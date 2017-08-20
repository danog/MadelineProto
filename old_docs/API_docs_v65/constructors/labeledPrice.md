---
title: labeledPrice
description: labeledPrice attributes, type and example
---
## Constructor: labeledPrice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|label|[string](../types/string.md) | Yes|
|amount|[long](../types/long.md) | Yes|



### Type: [LabeledPrice](../types/LabeledPrice.md)


### Example:

```
$labeledPrice = ['_' => 'labeledPrice', 'label' => 'string', 'amount' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "labeledPrice", "label": "string", "amount": long}
```


Or, if you're into Lua:  


```
labeledPrice={_='labeledPrice', label='string', amount=long}

```


