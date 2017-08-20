---
title: updateNewMessage
description: New message received, maybe outcoming message sent from other device
---
## Constructor: updateNewMessage  
[Back to constructors index](index.md)



New message received, maybe outcoming message sent from other device

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message|[message](../types/message.md) | Yes|New message|
|disable\_notification|[Bool](../types/Bool.md) | Yes|If true, notification about the message should be disabled|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewMessage = ['_' => 'updateNewMessage', 'message' => message, 'disable_notification' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewMessage", "message": message, "disable_notification": Bool}
```


Or, if you're into Lua:  


```
updateNewMessage={_='updateNewMessage', message=message, disable_notification=Bool}

```


