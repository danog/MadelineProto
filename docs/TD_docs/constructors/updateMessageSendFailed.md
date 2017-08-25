---
title: updateMessageSendFailed
description: Message fails to send. Be aware that some being sent messages can be irrecoverably deleted and updateDeleteMessages will come instead of this update
---
## Constructor: updateMessageSendFailed  
[Back to constructors index](index.md)



Message fails to send. Be aware that some being sent messages can be irrecoverably deleted and updateDeleteMessages will come instead of this update

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message|[message](../types/message.md) | Yes|Information about failed to send message|
|old\_message\_id|[int53](../types/int53.md) | Yes|Previous temporary message identifier|
|error\_code|[int](../types/int.md) | Yes|Error code|
|error\_message|[string](../types/string.md) | Yes|Error message|



### Type: [Update](../types/Update.md)


