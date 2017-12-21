---
title: updateChatOrder
description: Order of the chat in the chat list has changed. Instead of that update updateChatTopMessage, updateChatIsPinned or updateChatDraftMessage may be sent
---
## Constructor: updateChatOrder  
[Back to constructors index](index.md)



Order of the chat in the chat list has changed. Instead of that update updateChatTopMessage, updateChatIsPinned or updateChatDraftMessage may be sent

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier|
|order|[int64](../constructors/int64.md) | Yes|New value of the order|



### Type: [Update](../types/Update.md)


