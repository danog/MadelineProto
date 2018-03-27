---
title: req_pq
description: Requests PQ for factorization
---
## Method: req\_pq  
[Back to methods index](index.md)


Requests PQ for factorization

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|nonce|[int128](../types/int128.md) | Yes|Random number for cryptographic security|


### Return type: [ResPQ](../types/ResPQ.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$ResPQ = $MadelineProto->req_pq(['nonce' => int128, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

