---
title: inputMessageVideo
description: Video message
---
## Constructor: inputMessageVideo  
[Back to constructors index](index.md)



Video message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|video|[InputFile](../types/InputFile.md) | Yes|Video to send|
|thumb|[InputThumb](../types/InputThumb.md) | Yes|Video thumb, if available|
|added\_sticker\_file\_ids|Array of [int](../constructors/int.md) | Yes|File identifiers of stickers added onto the video|
|duration|[int](../types/int.md) | Yes|Duration of video in seconds|
|width|[int](../types/int.md) | Yes|Video width|
|height|[int](../types/int.md) | Yes|Video height|
|caption|[string](../types/string.md) | Yes|Video caption, 0-200 characters|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageVideo = ['_' => 'inputMessageVideo', 'video' => InputFile, 'thumb' => InputThumb, 'added_sticker_file_ids' => [int], 'duration' => int, 'width' => int, 'height' => int, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageVideo", "video": InputFile, "thumb": InputThumb, "added_sticker_file_ids": [int], "duration": int, "width": int, "height": int, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMessageVideo={_='inputMessageVideo', video=InputFile, thumb=InputThumb, added_sticker_file_ids={int}, duration=int, width=int, height=int, caption='string'}

```


