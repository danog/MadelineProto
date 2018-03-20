---
title: messages.readFeaturedStickers
description: messages.readFeaturedStickers parameters, return type and example
---
## Method: messages.readFeaturedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [CLICK ME long](../types/long.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->readFeaturedStickers(['id' => [long, long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readFeaturedStickers`

Parameters:

id - Json encoded  array of long




Or, if you're into Lua:

```
Bool = messages.readFeaturedStickers({id={long}, })
```

