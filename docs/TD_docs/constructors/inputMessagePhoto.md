---
title: inputMessagePhoto
description: Photo message
---
## Constructor: inputMessagePhoto  
[Back to constructors index](index.md)



Photo message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|photo|[InputFile](../types/InputFile.md) | Yes|Photo to send|
|thumb|[InputThumb](../types/InputThumb.md) | Yes|Photo thumb to send, is sent to the other party in secret chats only|
|added\_sticker\_file\_ids|Array of [int](../constructors/int.md) | Yes|File identifiers of stickers added onto the photo|
|width|[int](../types/int.md) | Yes|Photo width|
|height|[int](../types/int.md) | Yes|Photo height|
|caption|[string](../types/string.md) | Yes|Photo caption, 0-200 characters|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessagePhoto = ['_' => 'inputMessagePhoto', 'photo' => InputFile, 'thumb' => InputThumb, 'added_sticker_file_ids' => [int], 'width' => int, 'height' => int, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessagePhoto", "photo": InputFile, "thumb": InputThumb, "added_sticker_file_ids": [int], "width": int, "height": int, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMessagePhoto={_='inputMessagePhoto', photo=InputFile, thumb=InputThumb, added_sticker_file_ids={int}, width=int, height=int, caption='string'}

```


