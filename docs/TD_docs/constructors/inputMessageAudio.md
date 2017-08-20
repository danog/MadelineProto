---
title: inputMessageAudio
description: Audio message
---
## Constructor: inputMessageAudio  
[Back to constructors index](index.md)



Audio message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|audio|[InputFile](../types/InputFile.md) | Yes|Audio file to send|
|album\_cover\_thumb|[InputThumb](../types/InputThumb.md) | Yes|Thumb of the album's cover, if available|
|duration|[int](../types/int.md) | Yes|Duration of audio in seconds, may be replaced by the server|
|title|[string](../types/string.md) | Yes|Title of the audio, 0-64 characters, may be replaced by the server|
|performer|[string](../types/string.md) | Yes|Performer of the audio, 0-64 characters, may be replaced by the server|
|caption|[string](../types/string.md) | Yes|Audio caption, 0-200 characters|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageAudio = ['_' => 'inputMessageAudio', 'audio' => InputFile, 'album_cover_thumb' => InputThumb, 'duration' => int, 'title' => 'string', 'performer' => 'string', 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageAudio", "audio": InputFile, "album_cover_thumb": InputThumb, "duration": int, "title": "string", "performer": "string", "caption": "string"}
```


Or, if you're into Lua:  


```
inputMessageAudio={_='inputMessageAudio', audio=InputFile, album_cover_thumb=InputThumb, duration=int, title='string', performer='string', caption='string'}

```


