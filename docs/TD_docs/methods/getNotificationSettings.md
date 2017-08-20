---
title: getNotificationSettings
description: Returns notification settings for a given scope
---
## Method: getNotificationSettings  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns notification settings for a given scope

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|scope|[NotificationSettingsScope](../types/NotificationSettingsScope.md) | Yes|Scope to return information about notification settings|


### Return type: [NotificationSettings](../types/NotificationSettings.md)

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

$NotificationSettings = $MadelineProto->getNotificationSettings(['scope' => NotificationSettingsScope, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getNotificationSettings
* params - `{"scope": NotificationSettingsScope, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getNotificationSettings`

Parameters:

scope - Json encoded NotificationSettingsScope




Or, if you're into Lua:

```
NotificationSettings = getNotificationSettings({scope=NotificationSettingsScope, })
```

