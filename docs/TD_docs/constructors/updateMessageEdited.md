---
title: updateMessageEdited
description: Message was edited. Changes in the message content will come in a separate updateMessageContent
---
## Constructor: updateMessageEdited  
[Back to constructors index](index.md)



Message was edited. Changes in the message content will come in a separate updateMessageContent

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier|
|message\_id|[int53](../types/int53.md) | Yes|Message identifier|
|edit\_date|[int](../types/int.md) | Yes|Date the message was edited, unix time|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|New message reply markup, nullable|



### Type: [Update](../types/Update.md)


