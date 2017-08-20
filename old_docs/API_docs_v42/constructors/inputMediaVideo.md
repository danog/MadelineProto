---
title: inputMediaVideo
description: inputMediaVideo attributes, type and example
---
## Constructor: inputMediaVideo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[InputVideo](../types/InputVideo.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaVideo = ['_' => 'inputMediaVideo', 'id' => InputVideo, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaVideo", "id": InputVideo, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMediaVideo={_='inputMediaVideo', id=InputVideo, caption='string'}

```


