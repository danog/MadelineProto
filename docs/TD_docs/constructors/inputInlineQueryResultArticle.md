---
title: inputInlineQueryResultArticle
description: Represents link to an article or web page
---
## Constructor: inputInlineQueryResultArticle  
[Back to constructors index](index.md)



Represents link to an article or web page

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|url|[string](../types/string.md) | Yes|Url of the result, if exists|
|hide\_url|[Bool](../types/Bool.md) | Yes|True, if url must be not shown|
|title|[string](../types/string.md) | Yes|Title of the result|
|description|[string](../types/string.md) | Yes|Short description of the result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultArticle = ['_' => 'inputInlineQueryResultArticle', 'id' => 'string', 'url' => 'string', 'hide_url' => Bool, 'title' => 'string', 'description' => 'string', 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int, 'reply_markup' => ReplyMarkup, 'input_message_content' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultArticle", "id": "string", "url": "string", "hide_url": Bool, "title": "string", "description": "string", "thumb_url": "string", "thumb_width": int, "thumb_height": int, "reply_markup": ReplyMarkup, "input_message_content": InputMessageContent}
```


Or, if you're into Lua:  


```
inputInlineQueryResultArticle={_='inputInlineQueryResultArticle', id='string', url='string', hide_url=Bool, title='string', description='string', thumb_url='string', thumb_width=int, thumb_height=int, reply_markup=ReplyMarkup, input_message_content=InputMessageContent}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


