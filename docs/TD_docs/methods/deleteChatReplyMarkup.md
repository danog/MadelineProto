---
title: deleteChatReplyMarkup
description: Deletes default reply markup from chat. This method needs to be called after one-time keyboard or ForceReply reply markup has been used. UpdateChatReplyMarkup will be send if reply markup will be changed
---
## Method: deleteChatReplyMarkup  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes default reply markup from chat. This method needs to be called after one-time keyboard or ForceReply reply markup has been used. UpdateChatReplyMarkup will be send if reply markup will be changed

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|message\_id|[int53](../types/int53.md) | Yes|Message identifier of used keyboard|


### Return type: [Ok](../types/Ok.md)

