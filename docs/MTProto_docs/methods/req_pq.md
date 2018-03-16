---
title: req_pq
description: req_pq parameters, return type and example
---
## Method: req\_pq  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|nonce|[int128](../types/int128.md) | Yes|


### Return type: [ResPQ](../types/ResPQ.md)

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

$ResPQ = $MadelineProto->req_pq(['nonce' => int128, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - req_pq
* params - `{"nonce": int128, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/req_pq`

Parameters:

nonce - Json encoded int128




Or, if you're into Lua:

```
ResPQ = req_pq({nonce=int128, })
```

