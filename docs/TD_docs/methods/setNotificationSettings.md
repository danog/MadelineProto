---
title: setNotificationSettings
description: Changes notification settings for a given scope
---
## Method: setNotificationSettings  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes notification settings for a given scope

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|scope|[NotificationSettingsScope](../types/NotificationSettingsScope.md) | Yes|Scope to change notification settings|
|notification\_settings|[notificationSettings](../types/notificationSettings.md) | Yes|New notification settings for given scope|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->setNotificationSettings(['scope' => NotificationSettingsScope, 'notification_settings' => notificationSettings, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - setNotificationSettings
* params - `{"scope": NotificationSettingsScope, "notification_settings": notificationSettings, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/setNotificationSettings`

Parameters:

scope - Json encoded NotificationSettingsScope

notification_settings - Json encoded notificationSettings




Or, if you're into Lua:

```
Ok = setNotificationSettings({scope=NotificationSettingsScope, notification_settings=notificationSettings, })
```

