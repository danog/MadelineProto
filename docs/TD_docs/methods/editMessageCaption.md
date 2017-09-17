---
title: editMessageCaption
description: Edits message content caption. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side
---
## Method: editMessageCaption  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Edits message content caption. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat the message belongs to|
|message\_id|[int53](../types/int53.md) | Yes|Identifier of the message|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Bots only. New message reply markup|
|caption|[string](../types/string.md) | Yes|New message content caption, 0-200 characters|


### Return type: [Message](../types/Message.md)

