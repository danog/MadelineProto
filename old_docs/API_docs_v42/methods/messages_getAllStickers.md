---
title: messages.getAllStickers
description: messages.getAllStickers parameters, return type and example
---
## Method: messages.getAllStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[CLICK ME string](../types/string.md) | Yes|


### Return type: [messages\_AllStickers](../types/messages_AllStickers.md)

### Can bots use this method: **NO**


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

$messages_AllStickers = $MadelineProto->messages->getAllStickers(['hash' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAllStickers`

Parameters:

hash - Json encoded string




Or, if you're into Lua:

```
messages_AllStickers = messages.getAllStickers({hash='string', })
```

