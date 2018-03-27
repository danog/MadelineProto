---
title: invokeWithLayer18
description: Invoke this method with layer 18
---
## Method: invokeWithLayer18  
[Back to methods index](index.md)


Invoke this method with layer 18

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query|[!X](../types/!X.md) | Yes|The method call|


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

$X = $MadelineProto->invokeWithLayer18(['query' => !X, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - invokeWithLayer18
* params - `{"query": !X, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/invokeWithLayer18`

Parameters:

query - Json encoded !X




Or, if you're into Lua:

```
X = invokeWithLayer18({query=!X, })
```

