---
title: inputMessageAnimation
description: Animation message
---
## Constructor: inputMessageAnimation  
[Back to constructors index](index.md)



Animation message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|animation|[InputFile](../types/InputFile.md) | Yes|Animation file to send|
|thumb|[InputThumb](../types/InputThumb.md) | Yes|Animation thumb, if available|
|width|[int](../types/int.md) | Yes|Width of the animation, may be replaced by the server|
|height|[int](../types/int.md) | Yes|Height of the animation, may be replaced by the server|
|caption|[string](../types/string.md) | Yes|Animation caption, 0-200 characters|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageAnimation = ['_' => 'inputMessageAnimation', 'animation' => InputFile, 'thumb' => InputThumb, 'width' => int, 'height' => int, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageAnimation", "animation": InputFile, "thumb": InputThumb, "width": int, "height": int, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMessageAnimation={_='inputMessageAnimation', animation=InputFile, thumb=InputThumb, width=int, height=int, caption='string'}

```


