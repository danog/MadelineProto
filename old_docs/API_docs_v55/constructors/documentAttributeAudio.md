---
title: documentAttributeAudio
description: documentAttributeAudio attributes, type and example
---
## Constructor: documentAttributeAudio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|voice|[Bool](../types/Bool.md) | Optional|
|duration|[int](../types/int.md) | Yes|
|title|[string](../types/string.md) | Optional|
|performer|[string](../types/string.md) | Optional|
|waveform|[bytes](../types/bytes.md) | Optional|



### Type: [DocumentAttribute](../types/DocumentAttribute.md)


### Example:

```
$documentAttributeAudio = ['_' => 'documentAttributeAudio', 'voice' => Bool, 'duration' => int, 'title' => 'string', 'performer' => 'string', 'waveform' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "documentAttributeAudio", "voice": Bool, "duration": int, "title": "string", "performer": "string", "waveform": "bytes"}
```


Or, if you're into Lua:  


```
documentAttributeAudio={_='documentAttributeAudio', voice=Bool, duration=int, title='string', performer='string', waveform='bytes'}

```


