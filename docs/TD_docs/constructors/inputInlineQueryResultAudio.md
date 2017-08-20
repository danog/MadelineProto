---
title: inputInlineQueryResultAudio
description: Represents link to a mp3 audio file
---
## Constructor: inputInlineQueryResultAudio  
[Back to constructors index](index.md)



Represents link to a mp3 audio file

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the audio|
|performer|[string](../types/string.md) | Yes|Performer of the audio|
|audio\_url|[string](../types/string.md) | Yes|Url of the audio file|
|audio\_duration|[int](../types/int.md) | Yes|Audio duration in seconds|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageAudio or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultAudio = ['_' => 'inputInlineQueryResultAudio', 'id' => 'string', 'title' => 'string', 'performer' => 'string', 'audio_url' => 'string', 'audio_duration' => int, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultAudio", "id": "string", "title": "string", "performer": "string", "audio_url": "string", "audio_duration": int, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent}
```


Or, if you're into Lua:  


```
inputInlineQueryResultAudio={_='inputInlineQueryResultAudio', id='string', title='string', performer='string', audio_url='string', audio_duration=int, reply_markup=ReplyMarkup, input_message_content=InputMessageContent}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


