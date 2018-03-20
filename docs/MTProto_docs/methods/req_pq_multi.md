---
title: req_pq_multi
description: Requests PQ for factorization (new version)
---
## Method: req\_pq\_multi  
[Back to methods index](index.md)


Requests PQ for factorization (new version)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|nonce|[CLICK ME int128](../types/int128.md) | Yes|Random number for cryptographic security|


### Return type: [ResPQ](../types/ResPQ.md)

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

$ResPQ = $MadelineProto->req_pq_multi(['nonce' => int128, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - req_pq_multi
* params - `{"nonce": int128, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/req_pq_multi`

Parameters:

nonce - Json encoded int128




Or, if you're into Lua:

```
ResPQ = req_pq_multi({nonce=int128, })
```

