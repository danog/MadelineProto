---
title: pageBlockVideo
description: pageBlockVideo attributes, type and example
---
## Constructor: pageBlockVideo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|autoplay|[Bool](../types/Bool.md) | Optional|
|loop|[Bool](../types/Bool.md) | Optional|
|video\_id|[long](../types/long.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockVideo = ['_' => 'pageBlockVideo', 'autoplay' => Bool, 'loop' => Bool, 'video_id' => long, 'caption' => RichText];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockVideo", "autoplay": Bool, "loop": Bool, "video_id": long, "caption": RichText}
```


Or, if you're into Lua:  


```
pageBlockVideo={_='pageBlockVideo', autoplay=Bool, loop=Bool, video_id=long, caption=RichText}

```


