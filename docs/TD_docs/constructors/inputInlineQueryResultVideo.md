---
title: inputInlineQueryResultVideo
description: Represents link to a page containing an embedded video player or a video file
---
## Constructor: inputInlineQueryResultVideo  
[Back to constructors index](index.md)



Represents link to a page containing an embedded video player or a video file

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|title|[string](../types/string.md) | Yes|Title of the result|
|description|[string](../types/string.md) | Yes|Short description of the result, if known|
|thumb\_url|[string](../types/string.md) | Yes|Url of the video thumb (jpeg), if exists|
|video\_url|[string](../types/string.md) | Yes|Url of the embedded video player or video file|
|mime\_type|[string](../types/string.md) | Yes|MIME type of the content of video url, only "text/html" or "video/mp4" are allowed now|
|video\_width|[int](../types/int.md) | Yes|Video width|
|video\_height|[int](../types/int.md) | Yes|Video height|
|video\_duration|[int](../types/int.md) | Yes|Video duration in seconds|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|
|input\_message\_content|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of the message to be sent, should be of type inputMessageText or inputMessageVideo or InputMessageLocation or InputMessageVenue or InputMessageContact|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


