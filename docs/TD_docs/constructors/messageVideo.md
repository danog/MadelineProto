---
title: messageVideo
description: Video message
---
## Constructor: messageVideo  
[Back to constructors index](index.md)



Video message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|video|[video](../types/video.md) | Yes|Message content|
|caption|[string](../types/string.md) | Yes|Video caption|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageVideo = ['_' => 'messageVideo', 'video' => video, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageVideo", "video": video, "caption": "string"}
```


Or, if you're into Lua:  


```
messageVideo={_='messageVideo', video=video, caption='string'}

```


