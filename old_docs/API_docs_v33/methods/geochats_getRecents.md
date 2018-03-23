---
title: geochats.getRecents
description: Get recent geochats
---
## Method: geochats.getRecents  
[Back to methods index](index.md)


Get recent geochats

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[CLICK ME int](../types/int.md) | Yes|offset|
|limit|[CLICK ME int](../types/int.md) | Yes|Number of results to return|


### Return type: [geochats\_Messages](../types/geochats_Messages.md)

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

$geochats_Messages = $MadelineProto->geochats->getRecents(['offset' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.getRecents
* params - `{"offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.getRecents`

Parameters:

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
geochats_Messages = geochats.getRecents({offset=int, limit=int, })
```

