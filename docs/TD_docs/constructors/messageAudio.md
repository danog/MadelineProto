---
title: messageAudio
description: Audio message
---
## Constructor: messageAudio  
[Back to constructors index](index.md)



Audio message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|audio|[audio](../types/audio.md) | Yes|Message content|
|caption|[string](../types/string.md) | Yes|Audio caption|
|is\_listened|[Bool](../types/Bool.md) | Yes|True, if the audio message was listened to|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageAudio = ['_' => 'messageAudio', 'audio' => audio, 'caption' => 'string', 'is_listened' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageAudio", "audio": audio, "caption": "string", "is_listened": Bool}
```


Or, if you're into Lua:  


```
messageAudio={_='messageAudio', audio=audio, caption='string', is_listened=Bool}

```


