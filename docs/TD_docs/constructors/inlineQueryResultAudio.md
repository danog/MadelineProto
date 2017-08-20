---
title: inlineQueryResultAudio
description: Represents an audio cached on the telegram server
---
## Constructor: inlineQueryResultAudio  
[Back to constructors index](index.md)



Represents an audio cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|audio|[audio](../types/audio.md) | Yes|The audio|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultAudio = ['_' => 'inlineQueryResultAudio', 'id' => 'string', 'audio' => audio];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultAudio", "id": "string", "audio": audio}
```


Or, if you're into Lua:  


```
inlineQueryResultAudio={_='inlineQueryResultAudio', id='string', audio=audio}

```


