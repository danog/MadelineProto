---
title: updateNotificationSettings
description: Notification settings for some chats was updated
---
## Constructor: updateNotificationSettings  
[Back to constructors index](index.md)



Notification settings for some chats was updated

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|scope|[NotificationSettingsScope](../types/NotificationSettingsScope.md) | Yes|Kinds of chats for which notification settings was updated|
|notification\_settings|[notificationSettings](../types/notificationSettings.md) | Yes|New notification settings|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNotificationSettings = ['_' => 'updateNotificationSettings', 'scope' => NotificationSettingsScope, 'notification_settings' => notificationSettings];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNotificationSettings", "scope": NotificationSettingsScope, "notification_settings": notificationSettings}
```


Or, if you're into Lua:  


```
updateNotificationSettings={_='updateNotificationSettings', scope=NotificationSettingsScope, notification_settings=notificationSettings}

```


