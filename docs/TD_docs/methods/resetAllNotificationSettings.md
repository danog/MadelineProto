---
title: resetAllNotificationSettings
description: Resets all notification settings to the default value. By default the only muted chats are supergroups, sound is set to 'default' and message previews are showed
---
## Method: resetAllNotificationSettings  
[Back to methods index](index.md)


Resets all notification settings to the default value. By default the only muted chats are supergroups, sound is set to 'default' and message previews are showed

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->resetAllNotificationSettings();
```

Or, if you're into Lua:

```
Ok = resetAllNotificationSettings({})
```

