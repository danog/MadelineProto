---
title: webPage
description: Describes web page preview
---
## Constructor: webPage  
[Back to constructors index](index.md)



Describes web page preview

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|url|[string](../types/string.md) | Yes|Original URL of link|
|display\_url|[string](../types/string.md) | Yes|URL to display|
|type|[string](../types/string.md) | Yes|Type of web page: article, photo, audio, video, document, profile, app or something other|
|site\_name|[string](../types/string.md) | Yes|Short name of the site (i.e. Google Docs or App Store)|
|title|[string](../types/string.md) | Yes|Title of the content|
|description|[string](../types/string.md) | Yes|Description of the content|
|photo|[photo](../types/photo.md) | Yes|Image representing the content, nullable|
|embed\_url|[string](../types/string.md) | Yes|Url to show embedded preview|
|embed\_type|[string](../types/string.md) | Yes|MIME type of embedded preview, i.e. text/html or video/mp4|
|embed\_width|[int](../types/int.md) | Yes|Width of embedded preview|
|embed\_height|[int](../types/int.md) | Yes|Height of embedded preview|
|duration|[int](../types/int.md) | Yes|Duration of the content in seconds|
|author|[string](../types/string.md) | Yes|Author of the content|
|animation|[animation](../types/animation.md) | Yes|Preview as an Animation if available, nullable|
|audio|[audio](../types/audio.md) | Yes|Preview as an Audio if available, nullable|
|document|[document](../types/document.md) | Yes|Preview as a Document if available (currently only for small pdf files and zip archives), nullable|
|sticker|[sticker](../types/sticker.md) | Yes|Preview as a Sticker for small .webp files if available, nullable|
|video|[video](../types/video.md) | Yes|Preview as a Video if available, nullable|
|video\_note|[videoNote](../types/videoNote.md) | Yes|Preview as a VideoNote if available, nullable|
|voice|[voice](../types/voice.md) | Yes|Preview as a Voice if available, nullable|
|has\_instant\_view|[Bool](../types/Bool.md) | Yes|True if web page has instant view|



### Type: [WebPage](../types/WebPage.md)


