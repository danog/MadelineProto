---
title: inputMessageSticker
description: Sticker message
---
## Constructor: inputMessageSticker  
[Back to constructors index](index.md)



Sticker message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sticker|[InputFile](../types/InputFile.md) | Yes|Sticker to send|
|thumb|[InputThumb](../types/InputThumb.md) | Yes|Sticker thumb, if available|
|width|[int](../types/int.md) | Yes|Sticker width|
|height|[int](../types/int.md) | Yes|Sticker height|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageSticker = ['_' => 'inputMessageSticker', 'sticker' => InputFile, 'thumb' => InputThumb, 'width' => int, 'height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageSticker", "sticker": InputFile, "thumb": InputThumb, "width": int, "height": int}
```


Or, if you're into Lua:  


```
inputMessageSticker={_='inputMessageSticker', sticker=InputFile, thumb=InputThumb, width=int, height=int}

```


