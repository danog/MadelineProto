---
title: inputPhotoCrop
description: inputPhotoCrop attributes, type and example
---
## Constructor: inputPhotoCrop  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|crop\_left|[double](../types/double.md) | Yes|
|crop\_top|[double](../types/double.md) | Yes|
|crop\_width|[double](../types/double.md) | Yes|



### Type: [InputPhotoCrop](../types/InputPhotoCrop.md)


### Example:

```
$inputPhotoCrop = ['_' => 'inputPhotoCrop', 'crop_left' => double, 'crop_top' => double, 'crop_width' => double];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPhotoCrop", "crop_left": double, "crop_top": double, "crop_width": double}
```


Or, if you're into Lua:  


```
inputPhotoCrop={_='inputPhotoCrop', crop_left=double, crop_top=double, crop_width=double}

```


