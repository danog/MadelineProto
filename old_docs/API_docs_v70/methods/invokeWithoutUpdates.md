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


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$X = $MadelineProto->invokeWithoutUpdates(['query' => !X, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

