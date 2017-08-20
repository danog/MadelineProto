---
title: messageMediaAudio
description: messageMediaAudio attributes, type and example
---
## Constructor: messageMediaAudio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|audio|[Audio](../types/Audio.md) | Yes|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaAudio = ['_' => 'messageMediaAudio', 'audio' => Audio];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaAudio", "audio": Audio}
```


Or, if you're into Lua:  


```
messageMediaAudio={_='messageMediaAudio', audio=Audio}

```


