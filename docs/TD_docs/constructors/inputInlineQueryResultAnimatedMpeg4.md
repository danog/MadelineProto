---
title: inputInlineQueryResultAnimatedMpeg4
description: Represents link to an animated (i.e. without sound) H.264/MPEG-4 AVC video
---
## Constructor: inputInlineQueryResultAnimatedMpeg4  
[Back to constructors index](index.md)



Represents link to an animated (i.e. without sound) H.264/MPEG-4 AVC video

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the static result thumb (jpeg or gif), if exists|
|mpeg4\_url|[string](../types/string.md) | Yes|Url of the mp4-file (file size must not exceed 1MB)|
|mpeg4\_width|[int](../types/int.md) | Yes|Width of the video|
|mpeg4\_height|[int](../types/int.md) | Yes|Height of the video|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageAnimation or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultAnimatedMpeg4 = ['_' => 'inputInlineQueryResultAnimatedMpeg4', 'id' => 'string', 'title' => 'string', 'thumb_url' => 'string', 'mpeg4_url' => 'string', 'mpeg4_width' => int, 'mpeg4_height' => int, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultAnimatedMpeg4", "id": "string", "title": "string", "thumb_url": "string", "mpeg4_url": "string", "mpeg4_width": int, "mpeg4_height": int, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent}
```


Or, if you're into Lua:  


```
inputInlineQueryResultAnimatedMpeg4={_='inputInlineQueryResultAnimatedMpeg4', id='string', title='string', thumb_url='string', mpeg4_url='string', mpeg4_width=int, mpeg4_height=int, reply_markup=ReplyMarkup, input_message_content=InputMessageContent}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


