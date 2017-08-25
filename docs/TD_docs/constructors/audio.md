---
title: audio
description: Describes audio file. Audio is usually in mp3 format
---
## Constructor: audio  
[Back to constructors index](index.md)



Describes audio file. Audio is usually in mp3 format

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|duration|[int](../types/int.md) | Yes|Duration of the audio in seconds as defined by sender|
|title|[string](../types/string.md) | Yes|Title of the audio as defined by sender|
|performer|[string](../types/string.md) | Yes|Performer of the audio as defined by sender|
|file\_name|[string](../types/string.md) | Yes|Original name of a file as defined by sender|
|mime\_type|[string](../types/string.md) | Yes|MIME type of a file as defined by sender|
|album\_cover\_thumb|[photoSize](../types/photoSize.md) | Yes|Thumb of the album's cover as defined by sender. Full size thumb should be extracted from the downloaded file, nullable|
|audio|[file](../types/file.md) | Yes|File with the audio|



### Type: [Audio](../types/Audio.md)


