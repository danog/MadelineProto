---
title: channels.togglePreHistoryHidden
description: Enable or disable hidden history for new channel/supergroup users
---
## Method: channels.togglePreHistoryHidden  
[Back to methods index](index.md)


Enable or disable hidden history for new channel/supergroup users

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|enabled|[CLICK ME Bool](../types/Bool.md) | Yes|Enable or disable hidden history for new channel/supergroup users|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$Updates = $MadelineProto->channels->togglePreHistoryHidden(['channel' => InputChannel, 'enabled' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.togglePreHistoryHidden
* params - `{"channel": InputChannel, "enabled": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.togglePreHistoryHidden`

Parameters:

channel - Json encoded InputChannel

enabled - Json encoded Bool




Or, if you're into Lua:

```
Updates = channels.togglePreHistoryHidden({channel=InputChannel, enabled=Bool, })
```

