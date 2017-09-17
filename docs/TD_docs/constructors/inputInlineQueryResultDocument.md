---
title: inputInlineQueryResultDocument
description: Represents link to a file
---
## Constructor: inputInlineQueryResultDocument  
[Back to constructors index](index.md)



Represents link to a file

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the result|
|description|[string](../types/string.md) | Yes|Short description of the result, if known|
|document\_url|[string](../types/string.md) | Yes|Url of the file|
|mime\_type|[string](../types/string.md) | Yes|MIME type of the file content, only “application/pdf” and “application/zip” are allowed now|
|thumb\_url|[string](../types/string.md) | Yes|Url of the file thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Width of the thumb|
|thumb\_height|[int](../types/int.md) | Yes|Height of the thumb|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageDocument or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


