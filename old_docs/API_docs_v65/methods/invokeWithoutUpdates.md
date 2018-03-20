---
title: invokeWithoutUpdates
description: Invoke with method without returning updates in the socket
---
## Method: invokeWithoutUpdates  
[Back to methods index](index.md)


Invoke with method without returning updates in the socket

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query|[!X](../types/!X.md) | Yes|The query|


### Return type: [X](../types/X.md)

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

$X = $MadelineProto->invokeWithoutUpdates(['query' => !X, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - invokeWithoutUpdates
* params - `{"query": !X, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/invokeWithoutUpdates`

Parameters:

query - Json encoded !X




Or, if you're into Lua:

```
X = invokeWithoutUpdates({query=!X, })
```

