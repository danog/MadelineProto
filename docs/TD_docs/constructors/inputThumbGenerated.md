---
title: inputThumbGenerated
description: Generated thumb
---
## Constructor: inputThumbGenerated  
[Back to constructors index](index.md)



Generated thumb

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|original\_path|[string](../types/string.md) | Yes|Path to the original file|
|conversion|[string](../types/string.md) | Yes|String specifying conversion applied to the original file, should be persistent across application restart|
|width|[int](../types/int.md) | Yes|Thumb width, use 0 if unknown|
|height|[int](../types/int.md) | Yes|Thumb height, use 0 if unknown|



### Type: [InputThumb](../types/InputThumb.md)


### Example:

```
$inputThumbGenerated = ['_' => 'inputThumbGenerated', 'original_path' => 'string', 'conversion' => 'string', 'width' => int, 'height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputThumbGenerated", "original_path": "string", "conversion": "string", "width": int, "height": int}
```


Or, if you're into Lua:  


```
inputThumbGenerated={_='inputThumbGenerated', original_path='string', conversion='string', width=int, height=int}

```


