---
title: rpc_drop_answer
description: Do not send answer to provided request
---
## Method: rpc\_drop\_answer  
[Back to methods index](index.md)


Do not send answer to provided request

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|req\_msg\_id|[long](../types/long.md) | Yes|The message ID of the request|


### Return type: [RpcDropAnswer](../types/RpcDropAnswer.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$RpcDropAnswer = $MadelineProto->rpc_drop_answer(['req_msg_id' => long, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

