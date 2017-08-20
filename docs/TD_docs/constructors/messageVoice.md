---
title: messageVoice
description: Voice message
---
## Constructor: messageVoice  
[Back to constructors index](index.md)



Voice message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|voice|[voice](../types/voice.md) | Yes|Message content|
|caption|[string](../types/string.md) | Yes|Voice caption|
|is\_listened|[Bool](../types/Bool.md) | Yes|True, if the voice message was listened to|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageVoice = ['_' => 'messageVoice', 'voice' => voice, 'caption' => 'string', 'is_listened' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageVoice", "voice": voice, "caption": "string", "is_listened": Bool}
```


Or, if you're into Lua:  


```
messageVoice={_='messageVoice', voice=voice, caption='string', is_listened=Bool}

```


