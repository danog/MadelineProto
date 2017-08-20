---
title: inputInlineQueryResultVenue
description: Represents information about a venue
---
## Constructor: inputInlineQueryResultVenue  
[Back to constructors index](index.md)



Represents information about a venue

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|venue|[venue](../types/venue.md) | Yes|Result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultVenue = ['_' => 'inputInlineQueryResultVenue', 'id' => 'string', 'venue' => venue, 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultVenue", "id": "string", "venue": venue, "thumb_url": "string", "thumb_width": int, "thumb_height": int, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent}
```


Or, if you're into Lua:  


```
inputInlineQueryResultVenue={_='inputInlineQueryResultVenue', id='string', venue=venue, thumb_url='string', thumb_width=int, thumb_height=int, reply_markup=ReplyMarkup, input_message_content=InputMessageContent}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


