---
title: animation
description: Describes animation file. Animation should be encoded in gif or mp4 format
---
## Constructor: animation  
[Back to constructors index](index.md)



Describes animation file. Animation should be encoded in gif or mp4 format

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|width|[int](../types/int.md) | Yes|Width of the animation|
|height|[int](../types/int.md) | Yes|Height of the animation|
|file\_name|[string](../types/string.md) | Yes|Original name of a file as defined by sender|
|mime\_type|[string](../types/string.md) | Yes|MIME type of a file, usually "image/gif" or "video/mp4"|
|thumb|[photoSize](../types/photoSize.md) | Yes|Animation thumb, nullable|
|animation|[file](../types/file.md) | Yes|File with the animation|



### Type: [Animation](../types/Animation.md)


### Example:

```
$animation = ['_' => 'animation', 'width' => int, 'height' => int, 'file_name' => 'string', 'mime_type' => 'string', 'thumb' => photoSize, 'animation' => file];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "animation", "width": int, "height": int, "file_name": "string", "mime_type": "string", "thumb": photoSize, "animation": file}
```


Or, if you're into Lua:  


```
animation={_='animation', width=int, height=int, file_name='string', mime_type='string', thumb=photoSize, animation=file}

```


