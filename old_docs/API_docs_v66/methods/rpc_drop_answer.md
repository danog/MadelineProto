---
title: rpc_drop_answer
description: rpc_drop_answer parameters, return type and example
---
## Method: rpc\_drop\_answer  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|req\_msg\_id|[long](../types/long.md) | Yes|


### Return type: [RpcDropAnswer](../types/RpcDropAnswer.md)

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

$RpcDropAnswer = $MadelineProto->rpc_drop_answer(['req_msg_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - rpc_drop_answer
* params - `{"req_msg_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/rpc_drop_answer`

Parameters:

req_msg_id - Json encoded long




Or, if you're into Lua:

```
RpcDropAnswer = rpc_drop_answer({req_msg_id=long, })
```

