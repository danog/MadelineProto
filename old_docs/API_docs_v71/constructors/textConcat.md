---
title: textConcat
description: textConcat attributes, type and example
---
## Constructor: textConcat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|texts|Array of [RichText](../types/RichText.md) | Yes|



### Type: [RichText](../types/RichText.md)


### Example:

```
$textConcat = ['_' => 'textConcat', 'texts' => [RichText]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "textConcat", "texts": [RichText]}
```


Or, if you're into Lua:  


```
textConcat={_='textConcat', texts={RichText}}

```


