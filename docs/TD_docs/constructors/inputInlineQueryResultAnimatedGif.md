---
title: inputInlineQueryResultAnimatedGif
description: Represents link to an animated gif
---
## Constructor: inputInlineQueryResultAnimatedGif  
[Back to constructors index](index.md)



Represents link to an animated gif

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the static result thumb (jpeg or gif), if exists|
|gif\_url|[string](../types/string.md) | Yes|Url of the gif-file (file size must not exceed 1MB)|
|gif\_duration|[int](../types/int.md) | Yes|Duration of the gif in seconds|
|gif\_width|[int](../types/int.md) | Yes|Width of the gif|
|gif\_height|[int](../types/int.md) | Yes|Height of the gif|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageAnimation or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


