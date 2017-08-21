---
title: pageBlockAudio
description: pageBlockAudio attributes, type and example
---
## Constructor: pageBlockAudio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|audio\_id|[long](../types/long.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockAudio = ['_' => 'pageBlockAudio', 'audio_id' => long, 'caption' => RichText];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockAudio", "audio_id": long, "caption": RichText}
```


Or, if you're into Lua:  


```
pageBlockAudio={_='pageBlockAudio', audio_id=long, caption=RichText}

```


