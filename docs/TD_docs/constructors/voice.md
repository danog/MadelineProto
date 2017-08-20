---
title: voice
description: Describes voice record. Voice must be encoded with Opus codec and must be stored inside Ogg container
---
## Constructor: voice  
[Back to constructors index](index.md)



Describes voice record. Voice must be encoded with Opus codec and must be stored inside Ogg container

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|duration|[int](../types/int.md) | Yes|Duration of the voice record in seconds as defined by sender|
|waveform|[bytes](../types/bytes.md) | Yes|Waveform representation of the voice in 5-bit format|
|mime\_type|[string](../types/string.md) | Yes|MIME type of a file as defined by sender|
|voice|[file](../types/file.md) | Yes|File with the voice record|



### Type: [Voice](../types/Voice.md)


### Example:

```
$voice = ['_' => 'voice', 'duration' => int, 'waveform' => 'bytes', 'mime_type' => 'string', 'voice' => file];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "voice", "duration": int, "waveform": "bytes", "mime_type": "string", "voice": file}
```


Or, if you're into Lua:  


```
voice={_='voice', duration=int, waveform='bytes', mime_type='string', voice=file}

```


