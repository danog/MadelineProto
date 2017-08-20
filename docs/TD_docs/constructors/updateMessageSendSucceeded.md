---
title: updateMessageSendSucceeded
description: Message is successfully sent
---
## Constructor: updateMessageSendSucceeded  
[Back to constructors index](index.md)



Message is successfully sent

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message|[message](../types/message.md) | Yes|Information about sent message. Usually only message identifier, date and content are changed, but some other fields may also change|
|old\_message\_id|[long](../types/long.md) | Yes|Previous temporary message identifier|



### Type: [Update](../types/Update.md)


### Example:

```
$updateMessageSendSucceeded = ['_' => 'updateMessageSendSucceeded', 'message' => message, 'old_message_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateMessageSendSucceeded", "message": message, "old_message_id": long}
```


Or, if you're into Lua:  


```
updateMessageSendSucceeded={_='updateMessageSendSucceeded', message=message, old_message_id=long}

```


