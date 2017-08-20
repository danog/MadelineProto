---
title: notificationSettingsForChat
description: Notification settings applied to particular chat
---
## Constructor: notificationSettingsForChat  
[Back to constructors index](index.md)



Notification settings applied to particular chat

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|



### Type: [NotificationSettingsScope](../types/NotificationSettingsScope.md)


### Example:

```
$notificationSettingsForChat = ['_' => 'notificationSettingsForChat', 'chat_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "notificationSettingsForChat", "chat_id": long}
```


Or, if you're into Lua:  


```
notificationSettingsForChat={_='notificationSettingsForChat', chat_id=long}

```


