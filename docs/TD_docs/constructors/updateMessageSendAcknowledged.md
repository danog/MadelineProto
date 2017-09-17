---
title: updateMessageSendAcknowledged
description: Message send request has reached Telegram server. It doesn't mean that message send will be successful or even that message send request will be processed. Update will not come, unless option "use_quick_ack" is set to true. The update may come many times for the same message
---
## Constructor: updateMessageSendAcknowledged  
[Back to constructors index](index.md)



Message send request has reached Telegram server. It doesn't mean that message send will be successful or even that message send request will be processed. Update will not come, unless option "use_quick_ack" is set to true. The update may come many times for the same message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier of sent message|
|message\_id|[int53](../types/int53.md) | Yes|Temporary message identifier|



### Type: [Update](../types/Update.md)


