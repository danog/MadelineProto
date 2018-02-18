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
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

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

