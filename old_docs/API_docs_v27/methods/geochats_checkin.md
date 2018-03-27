---
title: geochats.checkin
description: Join a geochat
---
## Method: geochats.checkin  
[Back to methods index](index.md)


Join a geochat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|The geochat|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$geochats_StatedMessage = $MadelineProto->geochats->checkin(['peer' => InputGeoChat, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.checkin
* params - `{"peer": InputGeoChat, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.checkin`

Parameters:

peer - Json encoded InputGeoChat




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.checkin({peer=InputGeoChat, })
```

