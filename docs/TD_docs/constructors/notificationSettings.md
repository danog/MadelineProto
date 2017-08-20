---
title: notificationSettings
description: Contains information about notification settings for chat or chats
---
## Constructor: notificationSettings  
[Back to constructors index](index.md)



Contains information about notification settings for chat or chats

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|mute\_for|[int](../types/int.md) | Yes|Time left before notifications will be unmuted|
|sound|[string](../types/string.md) | Yes|Audio file name for notifications, iPhone apps only|
|show\_preview|[Bool](../types/Bool.md) | Yes|Display message text/media in notification|



### Type: [NotificationSettings](../types/NotificationSettings.md)


### Example:

```
$notificationSettings = ['_' => 'notificationSettings', 'mute_for' => int, 'sound' => 'string', 'show_preview' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "notificationSettings", "mute_for": int, "sound": "string", "show_preview": Bool}
```


Or, if you're into Lua:  


```
notificationSettings={_='notificationSettings', mute_for=int, sound='string', show_preview=Bool}

```


