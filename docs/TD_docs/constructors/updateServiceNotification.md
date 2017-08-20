---
title: updateServiceNotification
description: Service notification from the server. Upon receiving client should show popup with content of the notification
---
## Constructor: updateServiceNotification  
[Back to constructors index](index.md)



Service notification from the server. Upon receiving client should show popup with content of the notification

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|type|[string](../types/string.md) | Yes|Type of the notification|
|content|[MessageContent](../types/MessageContent.md) | Yes|Notification content|



### Type: [Update](../types/Update.md)


### Example:

```
$updateServiceNotification = ['_' => 'updateServiceNotification', 'type' => 'string', 'content' => MessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateServiceNotification", "type": "string", "content": MessageContent}
```


Or, if you're into Lua:  


```
updateServiceNotification={_='updateServiceNotification', type='string', content=MessageContent}

```


