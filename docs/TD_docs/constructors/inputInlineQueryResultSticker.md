---
title: inputInlineQueryResultSticker
description: Represents link to a webp sticker
---
## Constructor: inputInlineQueryResultSticker  
[Back to constructors index](index.md)



Represents link to a webp sticker

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the sticker thumb, if exists|
|sticker\_url|[string](../types/string.md) | Yes|Url of the webp sticker (file with a sticker must not exceed 5MB)|
|sticker\_width|[int](../types/int.md) | Yes|Width of the sticker|
|sticker\_height|[int](../types/int.md) | Yes|Height of the sticker|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageSticker or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultSticker = ['_' => 'inputInlineQueryResultSticker', 'id' => 'string', 'thumb_url' => 'string', 'sticker_url' => 'string', 'sticker_width' => int, 'sticker_height' => int, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultSticker", "id": "string", "thumb_url": "string", "sticker_url": "string", "sticker_width": int, "sticker_height": int, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent}
```


Or, if you're into Lua:  


```
inputInlineQueryResultSticker={_='inputInlineQueryResultSticker', id='string', thumb_url='string', sticker_url='string', sticker_width=int, sticker_height=int, reply_markup=ReplyMarkup, input_message_content=InputMessageContent}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


