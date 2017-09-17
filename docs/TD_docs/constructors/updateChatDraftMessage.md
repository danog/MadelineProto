---
title: updateChatDraftMessage
description: Chat draft has changed. Be aware that the update may come in the currently open chat with the old content of the draft. If the user has changed the content of the draft, the update shouldn't be applied
---
## Constructor: updateChatDraftMessage  
[Back to constructors index](index.md)



Chat draft has changed. Be aware that the update may come in the currently open chat with the old content of the draft. If the user has changed the content of the draft, the update shouldn't be applied

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier|
|draft\_message|[draftMessage](../types/draftMessage.md) | Yes|New chat draft_message, nullable|



### Type: [Update](../types/Update.md)


