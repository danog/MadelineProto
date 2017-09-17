---
title: inputInlineQueryResultPhoto
description: Represents link to a jpeg photo
---
## Constructor: inputInlineQueryResultPhoto  
[Back to constructors index](index.md)



Represents link to a jpeg photo

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the result, if known|
|description|[string](../types/string.md) | Yes|Short description of the result, if known|
|thumb\_url|[string](../types/string.md) | Yes|Url of the photo thumb, if exists|
|photo\_url|[string](../types/string.md) | Yes|Url of the jpeg photo (photo must not exceed 5MB)|
|photo\_width|[int](../types/int.md) | Yes|Width of the photo|
|photo\_height|[int](../types/int.md) | Yes|Height of the photo|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessagePhoto or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


