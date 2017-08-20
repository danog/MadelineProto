---
title: inlineQueryResultVideo
description: Represents a video cached on the telegram server
---
## Constructor: inlineQueryResultVideo  
[Back to constructors index](index.md)



Represents a video cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|video|[video](../types/video.md) | Yes|The video|
|title|[string](../types/string.md) | Yes|Title of the video|
|description|[string](../types/string.md) | Yes|Description of the video|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultVideo = ['_' => 'inlineQueryResultVideo', 'id' => 'string', 'video' => video, 'title' => 'string', 'description' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultVideo", "id": "string", "video": video, "title": "string", "description": "string"}
```


Or, if you're into Lua:  


```
inlineQueryResultVideo={_='inlineQueryResultVideo', id='string', video=video, title='string', description='string'}

```


