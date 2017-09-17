---
title: inputInlineQueryResultVoice
description: Represents link to a opus encoded audio file in ogg container
---
## Constructor: inputInlineQueryResultVoice  
[Back to constructors index](index.md)



Represents link to a opus encoded audio file in ogg container

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the voice file|
|voice\_url|[string](../types/string.md) | Yes|Url of the voice file|
|voice\_duration|[int](../types/int.md) | Yes|Voice duration in seconds|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageVoice or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


