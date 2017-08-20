---
title: inlineQueryResultVoice
description: Represents a voice cached on the telegram server
---
## Constructor: inlineQueryResultVoice  
[Back to constructors index](index.md)



Represents a voice cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|voice|[voice](../types/voice.md) | Yes|The voice|
|title|[string](../types/string.md) | Yes|Title of the voice file|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultVoice = ['_' => 'inlineQueryResultVoice', 'id' => 'string', 'voice' => voice, 'title' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultVoice", "id": "string", "voice": voice, "title": "string"}
```


Or, if you're into Lua:  


```
inlineQueryResultVoice={_='inlineQueryResultVoice', id='string', voice=voice, title='string'}

```


