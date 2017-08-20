---
title: inputInlineQueryResultLocation
description: Represents a point on the map
---
## Constructor: inputInlineQueryResultLocation  
[Back to constructors index](index.md)



Represents a point on the map

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|location|[location](../types/location.md) | Yes|Result|
|title|[string](../types/string.md) | Yes|Title of the result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultLocation = ['_' => 'inputInlineQueryResultLocation', 'id' => 'string', 'location' => location, 'title' => 'string', 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultLocation", "id": "string", "location": location, "title": "string", "thumb_url": "string", "thumb_width": int, "thumb_height": int, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent}
```


Or, if you're into Lua:  


```
inputInlineQueryResultLocation={_='inputInlineQueryResultLocation', id='string', location=location, title='string', thumb_url='string', thumb_width=int, thumb_height=int, reply_markup=ReplyMarkup, input_message_content=InputMessageContent}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


