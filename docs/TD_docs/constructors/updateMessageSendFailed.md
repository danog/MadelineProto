---
title: updateMessageSendFailed
description: Message fails to send. Be aware that some being sent messages can be irrecoverably deleted and updateDeleteMessages will come instead of this update (or doesn't come at all if deletion was done by call to deleteMessages)
---
## Constructor: updateMessageSendFailed  
[Back to constructors index](index.md)



Message fails to send. Be aware that some being sent messages can be irrecoverably deleted and updateDeleteMessages will come instead of this update (or doesn't come at all if deletion was done by call to deleteMessages)

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|message\_id|[long](../types/long.md) | Yes|Message identifier|
|error\_code|[int](../types/int.md) | Yes|Error code|
|error\_message|[string](../types/string.md) | Yes|Error message|



### Type: [Update](../types/Update.md)


### Example:

```
$updateMessageSendFailed = ['_' => 'updateMessageSendFailed', 'chat_id' => long, 'message_id' => long, 'error_code' => int, 'error_message' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateMessageSendFailed", "chat_id": long, "message_id": long, "error_code": int, "error_message": "string"}
```


Or, if you're into Lua:  


```
updateMessageSendFailed={_='updateMessageSendFailed', chat_id=long, message_id=long, error_code=int, error_message='string'}

```


