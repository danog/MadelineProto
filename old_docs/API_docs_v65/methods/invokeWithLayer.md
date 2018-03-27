---
title: invokeWithLayer
description: Invoke this method with layer X
---
## Method: invokeWithLayer  
[Back to methods index](index.md)


Invoke this method with layer X

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|layer|[int](../types/int.md) | Yes|The layer version|
|query|[!X](../types/!X.md) | Yes|The query|


### Return type: [X](../types/X.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$X = $MadelineProto->invokeWithLayer(['layer' => int, 'query' => !X, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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


