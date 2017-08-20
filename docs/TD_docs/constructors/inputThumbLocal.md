---
title: inputThumbLocal
description: Local file with the thumb
---
## Constructor: inputThumbLocal  
[Back to constructors index](index.md)



Local file with the thumb

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|path|[string](../types/string.md) | Yes|Path to the file with the thumb|
|width|[int](../types/int.md) | Yes|Thumb width, use 0 if unknown|
|height|[int](../types/int.md) | Yes|Thumb height, use 0 if unknown|



### Type: [InputThumb](../types/InputThumb.md)


### Example:

```
$inputThumbLocal = ['_' => 'inputThumbLocal', 'path' => 'string', 'width' => int, 'height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputThumbLocal", "path": "string", "width": int, "height": int}
```


Or, if you're into Lua:  


```
inputThumbLocal={_='inputThumbLocal', path='string', width=int, height=int}

```


