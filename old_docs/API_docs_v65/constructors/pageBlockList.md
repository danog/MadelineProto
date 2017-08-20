---
title: pageBlockList
description: pageBlockList attributes, type and example
---
## Constructor: pageBlockList  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ordered|[Bool](../types/Bool.md) | Yes|
|items|Array of [RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockList = ['_' => 'pageBlockList', 'ordered' => Bool, 'items' => [RichText]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockList", "ordered": Bool, "items": [RichText]}
```


Or, if you're into Lua:  


```
pageBlockList={_='pageBlockList', ordered=Bool, items={RichText}}

```


