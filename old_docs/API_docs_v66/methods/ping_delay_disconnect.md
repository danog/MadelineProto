---
title: ping_delay_disconnect
description: ping_delay_disconnect parameters, return type and example
---
## Method: ping\_delay\_disconnect  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ping\_id|[long](../types/long.md) | Yes|
|disconnect\_delay|[int](../types/int.md) | Yes|


### Return type: [Pong](../types/Pong.md)

### Can bots use this method: **YES**


### Example:


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

$Pong = $MadelineProto->ping_delay_disconnect(['ping_id' => long, 'disconnect_delay' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - ping_delay_disconnect
* params - `{"ping_id": long, "disconnect_delay": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/ping_delay_disconnect`

Parameters:

ping_id - Json encoded long

disconnect_delay - Json encoded int




Or, if you're into Lua:

```
Pong = ping_delay_disconnect({ping_id=long, disconnect_delay=int, })
```

