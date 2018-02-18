---
title: invokeWithLayer
description: invokeWithLayer parameters, return type and example
---
## Method: invokeWithLayer  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|layer|[int](../types/int.md) | Yes|
|query|[!X](../types/!X.md) | Yes|


### Return type: [X](../types/X.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|AUTH_BYTES_INVALID|The provided authorization is invalid|
|CDN_METHOD_INVALID|You can't call this method in a CDN DC|
|CONNECTION_API_ID_INVALID|The provided API id is invalid|
|CONNECTION_DEVICE_MODEL_EMPTY|Device model empty|
|CONNECTION_LANG_PACK_INVALID|Language pack invalid|
|CONNECTION_NOT_INITED|Connection not initialized|
|CONNECTION_SYSTEM_EMPTY|Connection system empty|
|INPUT_LAYER_INVALID|The provided layer is invalid|
|INVITE_HASH_EXPIRED|The invite link has expired|
|NEED_MEMBER_INVALID|The provided member is invalid|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|


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

$X = $MadelineProto->invokeWithLayer(['layer' => int, 'query' => !X, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - invokeWithLayer
* params - `{"layer": int, "query": !X, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/invokeWithLayer`

Parameters:

layer - Json encoded int

query - Json encoded !X




Or, if you're into Lua:

```
X = invokeWithLayer({layer=int, query=!X, })
```

